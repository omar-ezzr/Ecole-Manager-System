<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_summary_uses_records_and_isolates_academic_years(): void
    {
        $academicYear = AcademicYear::active()->firstOrFail();
        $classroom = Classroom::firstOrFail();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $enrollment = $student->currentEnrollment()->firstOrFail();
        $enrollment->update(['enrolled_at' => '2026-09-01']);

        foreach (['present', 'present', 'absent', 'late', 'excused'] as $offset => $status) {
            $this->attendance($enrollment, sprintf('2026-09-%02d', $offset + 1), $status);
        }

        $otherYear = AcademicYear::factory()->create([
            'name' => '2035-2036',
            'starts_at' => '2035-09-01',
            'ends_at' => '2036-07-31',
        ]);
        $otherEnrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $otherYear->id,
            'enrolled_at' => '2035-09-01',
            'left_at' => '2035-12-31',
        ]);
        $this->attendance($otherEnrollment, '2035-09-10', 'absent');

        $summary = AttendanceRecord::summarize(AttendanceRecord::query()
            ->forStudent($student)
            ->forAcademicYear($academicYear));

        $this->assertSame([
            'present' => 2,
            'absent' => 1,
            'late' => 1,
            'excused' => 1,
            'recorded' => 5,
        ], $summary);

        $director = $this->userWithRole(Role::ROLE_DIRECTOR);
        $this->actingAs($director)
            ->get(route('students.show', [
                'student' => $student,
                'attendance_academic_year_id' => $academicYear->id,
            ]))
            ->assertOk()
            ->assertViewHas('attendanceSummary', $summary)
            ->assertViewHas('selectedAttendanceYear', fn (?AcademicYear $year) => $year?->is($academicYear) === true)
            ->assertSee('Student Information')
            ->assertSee('Current Enrollment')
            ->assertSee('Enrollment History')
            ->assertSee('Academic Results')
            ->assertSee('Attendance Summary')
            ->assertSee('Recorded Days');

        $this->actingAs($director)
            ->get(route('dashboard', ['attendance_academic_year_id' => $academicYear->id]))
            ->assertOk()
            ->assertViewHas('attendanceSummary', $summary);
    }

    public function test_classroom_reporting_preserves_historical_context_after_transfer(): void
    {
        [$classroomA, $classroomB] = Classroom::orderBy('id')->limit(2)->get()->all();
        $yearA = AcademicYear::active()->firstOrFail();
        $student = Student::factory()->create(['classroom_id' => $classroomA->id]);
        $enrollmentA = $student->currentEnrollment()->firstOrFail();
        $enrollmentA->update(['enrolled_at' => '2026-09-01']);
        $oldAttendance = $this->attendance($enrollmentA, '2026-10-15', 'absent');

        $yearB = AcademicYear::factory()->create([
            'name' => '2027-2028',
            'starts_at' => '2027-09-01',
            'ends_at' => '2028-07-31',
        ]);
        $student->updateWithEnrollment(['classroom_id' => $classroomB->id], $yearB, '2027-09-01');
        $enrollmentB = $student->fresh()->currentEnrollment()->firstOrFail();
        $this->attendance($enrollmentB, '2027-09-10', 'present');

        $classroomAYearASummary = AttendanceRecord::summarize(AttendanceRecord::query()
            ->forAcademicYear($yearA)
            ->forClassroom($classroomA));
        $classroomBYearBSummary = AttendanceRecord::summarize(AttendanceRecord::query()
            ->forAcademicYear($yearB)
            ->forClassroom($classroomB));
        $classroomAOtherYearSummary = AttendanceRecord::summarize(AttendanceRecord::query()
            ->forAcademicYear($yearB)
            ->forClassroom($classroomA));
        $absenceCounts = AttendanceRecord::classroomStatusCounts(
            AttendanceRecord::query()->forAcademicYear($yearA),
            AttendanceRecord::STATUS_ABSENT
        );

        $this->assertSame(1, $classroomAYearASummary['absent']);
        $this->assertSame(1, $classroomAYearASummary['recorded']);
        $this->assertSame(1, $classroomBYearBSummary['present']);
        $this->assertSame(1, $classroomBYearBSummary['recorded']);
        $this->assertSame(0, $classroomAOtherYearSummary['recorded']);
        $this->assertSame($classroomA->id, $oldAttendance->fresh()->studentEnrollment->classroom_id);
        $this->assertSame($classroomB->id, $student->fresh()->classroom_id);
        $this->assertSame([[
            'classroom_id' => $classroomA->id,
            'label' => $classroomA->name,
            'total' => 1,
        ]], $absenceCounts->all());

        $director = $this->userWithRole(Role::ROLE_DIRECTOR);
        $this->actingAs($director)
            ->get(route('dashboard', [
                'attendance_academic_year_id' => $yearA->id,
                'attendance_classroom_id' => $classroomA->id,
            ]))
            ->assertOk()
            ->assertViewHas('attendanceSummary', $classroomAYearASummary);
        $this->actingAs($director)
            ->get(route('dashboard', [
                'attendance_academic_year_id' => $yearB->id,
                'attendance_classroom_id' => $classroomB->id,
            ]))
            ->assertOk()
            ->assertViewHas('attendanceSummary', $classroomBYearBSummary);
    }

    public function test_professor_dashboard_reporting_is_assignment_scoped_and_historical(): void
    {
        [$classroomA, $classroomB] = Classroom::orderBy('id')->limit(2)->get()->all();
        $yearA = AcademicYear::active()->firstOrFail();
        $professorA = $this->userWithRole(Role::ROLE_PROFESSOR);
        $assignmentA = TeachingAssignment::factory()->create([
            'professor_id' => $professorA->id,
            'classroom_id' => $classroomA->id,
            'academic_year_id' => $yearA->id,
        ]);
        $student = Student::factory()->create(['classroom_id' => $classroomA->id]);
        $enrollmentA = $student->currentEnrollment()->firstOrFail();
        $enrollmentA->update(['enrolled_at' => '2026-09-01']);
        $this->attendance($enrollmentA, '2026-10-15', 'absent');

        $yearB = AcademicYear::factory()->create([
            'name' => '2027-2028',
            'starts_at' => '2027-09-01',
            'ends_at' => '2028-07-31',
        ]);
        $student->updateWithEnrollment(['classroom_id' => $classroomB->id], $yearB, '2027-09-01');
        $enrollmentB = $student->fresh()->currentEnrollment()->firstOrFail();
        $this->attendance($enrollmentB, '2027-09-10', 'present');
        $professorB = $this->userWithRole(Role::ROLE_PROFESSOR);
        TeachingAssignment::factory()->create([
            'professor_id' => $professorB->id,
            'classroom_id' => $classroomB->id,
            'academic_year_id' => $yearB->id,
        ]);

        $this->actingAs($professorA)
            ->get(route('dashboard', ['attendance_academic_year_id' => $yearA->id]))
            ->assertOk()
            ->assertViewHas('attendanceSummary', fn (array $summary) => $summary['absent'] === 1
                && $summary['present'] === 0
                && $summary['recorded'] === 1)
            ->assertViewHas('attendanceClassrooms', fn ($classrooms) => $classrooms->contains('id', $classroomA->id)
                && ! $classrooms->contains('id', $classroomB->id));

        $this->actingAs($professorA)
            ->get(route('dashboard', [
                'attendance_academic_year_id' => $yearA->id,
                'attendance_classroom_id' => $classroomB->id,
            ]))
            ->assertForbidden();

        $this->actingAs($professorA)
            ->get('/absences')
            ->assertOk()
            ->assertSee($classroomA->name)
            ->assertDontSee($classroomB->name);

        $this->actingAs($professorB)
            ->get(route('dashboard', ['attendance_academic_year_id' => $yearA->id]))
            ->assertForbidden();
        $this->actingAs($professorB)
            ->get(route('dashboard', ['attendance_academic_year_id' => $yearB->id]))
            ->assertOk()
            ->assertViewHas('attendanceSummary', fn (array $summary) => $summary['present'] === 1
                && $summary['absent'] === 0
                && $summary['recorded'] === 1);

        $this->assertTrue($professorA->can('viewForAssignment', [AttendanceRecord::class, $assignmentA]));
    }

    public function test_global_and_restricted_roles_receive_only_authorized_dashboard_reporting(): void
    {
        $academicYear = AcademicYear::active()->firstOrFail();
        $classroom = Classroom::firstOrFail();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $enrollment = $student->currentEnrollment()->firstOrFail();
        $enrollment->update(['enrolled_at' => '2026-09-01']);
        $this->attendance($enrollment, '2026-09-10', 'absent');
        $this->attendance($enrollment, '2026-09-11', 'absent');
        $assignment = TeachingAssignment::factory()->create([
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $expected = [
            'present' => 0,
            'absent' => 2,
            'late' => 0,
            'excused' => 0,
            'recorded' => 2,
        ];

        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $director = $this->userWithRole(Role::ROLE_DIRECTOR);
        $secretary = $this->userWithRole(Role::ROLE_SECRETARY);

        foreach ([$admin, $director] as $user) {
            $this->actingAs($user)
                ->get(route('dashboard', ['attendance_academic_year_id' => $academicYear->id]))
                ->assertOk()
                ->assertViewHas('attendanceSummary', $expected)
                ->assertSee('Attendance Summary');
        }

        $this->actingAs($director)
            ->post(route('teaching-assignments.attendance.store', $assignment), [
                'date' => '2026-09-12',
                'attendance' => [[
                    'student_enrollment_id' => $enrollment->id,
                    'status' => 'present',
                ]],
            ])
            ->assertForbidden();

        $this->actingAs($secretary)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('attendanceSummary', null)
            ->assertDontSee('Attendance Summary');
        $this->actingAs($secretary)
            ->get('/absences')
            ->assertForbidden();
        $this->actingAs($secretary)
            ->get(route('dashboard', ['attendance_academic_year_id' => $academicYear->id]))
            ->assertForbidden();
    }

    private function attendance(
        StudentEnrollment $enrollment,
        string $date,
        string $status
    ): AttendanceRecord {
        return AttendanceRecord::create([
            'student_enrollment_id' => $enrollment->id,
            'date' => $date,
            'status' => $status,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
