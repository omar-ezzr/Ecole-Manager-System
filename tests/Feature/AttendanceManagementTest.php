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
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;


    protected function setUp(): void
    {
        parent::setUp();

        AttendanceRecord::query()->delete();
    }
    public function test_attendance_schema_has_enrollment_foreign_key_and_daily_unique_index(): void
    {
        $this->assertTrue(Schema::hasTable('attendance_records'));
        $this->assertTrue(Schema::hasColumns('attendance_records', [
            'id',
            'student_enrollment_id',
            'date',
            'status',
            'note',
            'created_at',
            'updated_at',
        ]));

        $foreignKey = collect(Schema::getForeignKeys('attendance_records'))
            ->first(fn (array $key) => $key['columns'] === ['student_enrollment_id']);

        $this->assertNotNull($foreignKey);
        $this->assertSame('student_enrollments', $foreignKey['foreign_table']);
        $this->assertSame(['id'], $foreignKey['foreign_columns']);
        $this->assertTrue(collect(Schema::getIndexes('attendance_records'))->contains(
            fn (array $index) => $index['unique']
                && $index['columns'] === ['student_enrollment_id', 'date']
        ));
    }

    public function test_database_rejects_duplicate_daily_attendance(): void
    {
        [, $enrollment] = $this->assignmentScenario();
        AttendanceRecord::create($this->recordPayload($enrollment, '2026-09-10'));

        $this->expectException(QueryException::class);
        AttendanceRecord::create($this->recordPayload($enrollment, '2026-09-10', 'absent'));
    }

    public function test_authorized_professor_can_open_save_and_idempotently_update_daily_attendance(): void
    {
        [$assignment, $enrollment, , $professor] = $this->assignmentScenario();

        $this->actingAs($professor)
            ->get(route('teaching-assignments.attendance.index', [
                'teaching_assignment' => $assignment,
                'date' => '2026-09-10',
            ]))
            ->assertOk()
            ->assertSee($enrollment->student->student_number)
            ->assertSee('Save Attendance');

        $this->actingAs($professor)
            ->post(route('teaching-assignments.attendance.store', $assignment), $this->bulkPayload(
                $enrollment,
                '2026-09-10',
                'absent',
                'First saved note'
            ))
            ->assertRedirect();

        $record = AttendanceRecord::firstOrFail();
        $this->assertSame('absent', $record->status);

        $this->actingAs($professor)
            ->get(route('teaching-assignments.attendance.index', [
                'teaching_assignment' => $assignment,
                'date' => '2026-09-10',
            ]))
            ->assertOk()
            ->assertSee('First saved note');

        $this->actingAs($professor)
            ->post(route('teaching-assignments.attendance.store', $assignment), $this->bulkPayload(
                $enrollment,
                '2026-09-10',
                'late',
                'Updated note'
            ))
            ->assertRedirect();

        $this->assertSame(1, AttendanceRecord::count());
        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'student_enrollment_id' => $enrollment->id,
            'date' => '2026-09-10',
            'status' => 'late',
            'note' => 'Updated note',
        ]);
    }

    public function test_unrelated_professor_cannot_manage_another_professors_assignment(): void
    {
        [$assignment, $enrollment] = $this->assignmentScenario();
        $otherProfessor = $this->professor();
        $otherClassroom = Classroom::whereKeyNot($assignment->classroom_id)->firstOrFail();
        TeachingAssignment::factory()->create([
            'professor_id' => $otherProfessor->id,
            'classroom_id' => $otherClassroom->id,
            'academic_year_id' => $assignment->academic_year_id,
        ]);

        $this->actingAs($otherProfessor)
            ->get(route('teaching-assignments.attendance.index', $assignment))
            ->assertForbidden();
        $this->actingAs($otherProfessor)
            ->post(
                route('teaching-assignments.attendance.store', $assignment),
                $this->bulkPayload($enrollment, '2026-09-10')
            )
            ->assertForbidden();

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_enrollment_from_another_classroom_is_rejected_without_partial_writes(): void
    {
        [$assignment, $validEnrollment, , $professor] = $this->assignmentScenario();
        $otherClassroom = Classroom::whereKeyNot($assignment->classroom_id)->firstOrFail();
        $otherStudent = Student::factory()->create(['classroom_id' => $otherClassroom->id]);
        $otherEnrollment = $otherStudent->currentEnrollment()->firstOrFail();
        $otherEnrollment->update(['enrolled_at' => '2026-09-01']);

        $payload = [
            'date' => '2026-09-10',
            'attendance' => [
                [
                    'student_enrollment_id' => $validEnrollment->id,
                    'status' => 'present',
                ],
                [
                    'student_enrollment_id' => $otherEnrollment->id,
                    'status' => 'absent',
                ],
            ],
        ];

        $this->actingAs($professor)
            ->post(route('teaching-assignments.attendance.store', $assignment), $payload)
            ->assertSessionHasErrors('attendance.1.student_enrollment_id');

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_enrollment_from_another_academic_year_is_rejected(): void
    {
        [$assignment, , $student, $professor] = $this->assignmentScenario();
        $otherYear = AcademicYear::factory()->create([
            'name' => '2035-2036',
            'starts_at' => '2035-09-01',
            'ends_at' => '2036-07-31',
        ]);
        $sameClassOtherYearEnrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'classroom_id' => $assignment->classroom_id,
            'academic_year_id' => $otherYear->id,
            'enrolled_at' => '2035-09-01',
            'left_at' => '2035-10-01',
        ]);

        $this->actingAs($professor)
            ->post(
                route('teaching-assignments.attendance.store', $assignment),
                $this->bulkPayload($sameClassOtherYearEnrollment, '2035-09-10')
            )
            ->assertSessionHasErrors('attendance.0.student_enrollment_id');

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_attendance_date_must_be_within_inclusive_enrollment_boundaries(): void
    {
        [$assignment, $enrollment, , $professor] = $this->assignmentScenario();
        $enrollment->update([
            'enrolled_at' => '2026-09-10',
            'left_at' => '2026-09-20',
        ]);

        $this->actingAs($professor)
            ->post(
                route('teaching-assignments.attendance.store', $assignment),
                $this->bulkPayload($enrollment, '2026-09-09')
            )
            ->assertSessionHasErrors('attendance.0.student_enrollment_id');

        foreach (['2026-09-10', '2026-09-20'] as $validDate) {
            $this->actingAs($professor)
                ->post(
                    route('teaching-assignments.attendance.store', $assignment),
                    $this->bulkPayload($enrollment, $validDate)
                )
                ->assertRedirect();
        }

        $this->actingAs($professor)
            ->post(
                route('teaching-assignments.attendance.store', $assignment),
                $this->bulkPayload($enrollment, '2026-09-21')
            )
            ->assertSessionHasErrors('attendance.0.student_enrollment_id');

        $this->assertSame(2, AttendanceRecord::count());
        $this->assertDatabaseHas('attendance_records', [
            'student_enrollment_id' => $enrollment->id,
            'date' => '2026-09-10',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'student_enrollment_id' => $enrollment->id,
            'date' => '2026-09-20',
        ]);
    }

    public function test_only_supported_attendance_statuses_are_accepted(): void
    {
        [$assignment, $enrollment, , $professor] = $this->assignmentScenario();

        $this->actingAs($professor)
            ->post(
                route('teaching-assignments.attendance.store', $assignment),
                $this->bulkPayload($enrollment, '2026-09-10', 'unknown')
            )
            ->assertSessionHasErrors('attendance.0.status');

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_transfer_preserves_old_attendance_and_historical_professor_access(): void
    {
        [$assignmentA, $enrollmentA, $student, $professorA] = $this->assignmentScenario();
        $attendance = AttendanceRecord::create($this->recordPayload(
            $enrollmentA,
            '2026-10-15',
            'absent',
            'Historical attendance'
        ));
        $classroomB = Classroom::whereKeyNot($assignmentA->classroom_id)->firstOrFail();
        $academicYear = $assignmentA->academicYear;
        $student->updateWithEnrollment(['classroom_id' => $classroomB->id], $academicYear, '2026-11-01');
        $enrollmentB = $student->fresh()->currentEnrollment()->firstOrFail();
        $professorB = $this->professor();
        $assignmentB = TeachingAssignment::factory()->create([
            'professor_id' => $professorB->id,
            'classroom_id' => $classroomB->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $this->assertSame($classroomB->id, $student->fresh()->classroom_id);
        $this->assertSame($enrollmentA->id, $attendance->fresh()->student_enrollment_id);
        $this->assertSame($assignmentA->classroom_id, $attendance->studentEnrollment->classroom_id);
        $this->assertTrue($professorA->can('update', $attendance));
        $this->assertFalse($professorB->can('update', $attendance));

        $this->actingAs($professorA)
            ->get(route('teaching-assignments.attendance.index', [
                'teaching_assignment' => $assignmentA,
                'date' => '2026-10-15',
            ]))
            ->assertOk()
            ->assertSee('Historical attendance');
        $this->actingAs($professorA)
            ->post(
                route('teaching-assignments.attendance.store', $assignmentA),
                $this->bulkPayload($enrollmentA, '2026-10-15', 'excused', 'Updated historically')
            )
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->actingAs($professorB)
            ->post(
                route('teaching-assignments.attendance.store', $assignmentB),
                $this->bulkPayload($enrollmentA, '2026-10-15', 'present')
            )
            ->assertSessionHasErrors('attendance.0.student_enrollment_id');

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendance->id,
            'student_enrollment_id' => $enrollmentA->id,
            'status' => 'excused',
            'note' => 'Updated historically',
        ]);
        $this->assertDatabaseMissing('attendance_records', [
            'student_enrollment_id' => $enrollmentB->id,
            'date' => '2026-10-15',
        ]);
    }

    public function test_administrator_director_and_secretariat_follow_attendance_permission_matrix(): void
    {
        [$assignment, $enrollment, , $professor] = $this->assignmentScenario();
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);
        $secretary = User::factory()->create();
        $secretary->assignRole(Role::ROLE_SECRETARY);

        $this->actingAs($professor)
            ->get(route('teaching-assignments.show', $assignment))
            ->assertOk()
            ->assertSee('Manage Grades')
            ->assertSee('Take Attendance');

        $this->actingAs($admin)
            ->post(
                route('teaching-assignments.attendance.store', $assignment),
                $this->bulkPayload($enrollment, '2026-09-10', 'absent', 'Visible to Director')
            )
            ->assertRedirect();

        $this->actingAs($director)
            ->get(route('teaching-assignments.show', $assignment))
            ->assertOk()
            ->assertSee('View Grades')
            ->assertDontSee('Manage Grades')
            ->assertSee('View Attendance')
            ->assertDontSee('Take Attendance');
        $this->actingAs($director)
            ->get(route('teaching-assignments.attendance.index', [
                'teaching_assignment' => $assignment,
                'date' => '2026-09-10',
            ]))
            ->assertOk()
            ->assertSee('Absent')
            ->assertSee('Visible to Director')
            ->assertDontSee('Save Attendance');
        $this->actingAs($director)
            ->post(
                route('teaching-assignments.attendance.store', $assignment),
                $this->bulkPayload($enrollment, '2026-09-10', 'present')
            )
            ->assertForbidden();

        $this->actingAs($secretary)
            ->get(route('teaching-assignments.show', $assignment))
            ->assertOk()
            ->assertSee('View Grades')
            ->assertDontSee('View Attendance')
            ->assertDontSee('Take Attendance');
        $this->actingAs($secretary)
            ->get(route('teaching-assignments.attendance.index', $assignment))
            ->assertForbidden();
        $this->actingAs($secretary)
            ->post(
                route('teaching-assignments.attendance.store', $assignment),
                $this->bulkPayload($enrollment, '2026-09-10')
            )
            ->assertForbidden();

        $this->assertDatabaseHas('attendance_records', [
            'student_enrollment_id' => $enrollment->id,
            'status' => 'absent',
        ]);
    }

    private function assignmentScenario(): array
    {
        $academicYear = AcademicYear::active()->firstOrFail();
        $classroom = Classroom::firstOrFail();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $enrollment = $student->currentEnrollment()->with('student')->firstOrFail();
        $enrollment->update(['enrolled_at' => '2026-09-01']);
        $professor = $this->professor();
        $assignment = TeachingAssignment::factory()->create([
            'professor_id' => $professor->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ]);

        return [$assignment->load('academicYear'), $enrollment->fresh('student'), $student, $professor];
    }

    private function professor(): User
    {
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);

        return $professor;
    }

    private function bulkPayload(
        StudentEnrollment $enrollment,
        string $date,
        string $status = 'present',
        ?string $note = null
    ): array {
        return [
            'date' => $date,
            'attendance' => [[
                'student_enrollment_id' => $enrollment->id,
                'status' => $status,
                'note' => $note,
            ]],
        ];
    }

    private function recordPayload(
        StudentEnrollment $enrollment,
        string $date,
        string $status = 'present',
        ?string $note = null
    ): array {
        return [
            'student_enrollment_id' => $enrollment->id,
            'date' => $date,
            'status' => $status,
            'note' => $note,
        ];
    }
}
