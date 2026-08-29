<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class StudentEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_creation_creates_current_academic_year_enrollment(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $classroom = Classroom::firstOrFail();
        $academicYear = AcademicYear::active()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('students.store'), $this->studentPayload($classroom, [
                'student_number' => 'ENR-CREATE-001',
            ]))
            ->assertRedirect(route('students.index'));

        $student = Student::where('student_number', 'ENR-CREATE-001')->firstOrFail();
        $this->assertSame($classroom->id, $student->classroom_id);
        $this->assertCount(1, $student->enrollments);
        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
            'left_at' => null,
        ]);
    }

    public function test_unrelated_or_same_classroom_edit_does_not_duplicate_enrollment(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $student = Student::factory()->create();
        $enrollment = $student->currentEnrollment()->firstOrFail();

        $this->actingAs($admin)
            ->put(route('students.update', $student), $this->studentPayload($student->classroom, [
                'student_number' => $student->student_number,
                'first_name' => 'Updated Name',
            ]))
            ->assertRedirect(route('students.index'));

        $this->assertSame('Updated Name', $student->fresh()->first_name);
        $this->assertSame(1, $student->enrollments()->count());
        $this->assertNull($enrollment->fresh()->left_at);
    }

    public function test_student_forms_and_crud_ignore_retired_absence_input(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $classroom = Classroom::firstOrFail();
        $existingStudent = Student::factory()->create(['classroom_id' => $classroom->id]);

        $this->actingAs($admin)
            ->get(route('students.create'))
            ->assertOk()
            ->assertDontSee('absences_count');
        $this->actingAs($admin)
            ->get(route('students.edit', $existingStudent))
            ->assertOk()
            ->assertDontSee('absences_count');
        $this->actingAs($admin)
            ->get(route('students.show', $existingStudent))
            ->assertOk()
            ->assertSee('Enrollment History')
            ->assertDontSee('absences_count')
            ->assertDontSee('Absences Count');

        $this->actingAs($admin)
            ->post(route('students.store'), $this->studentPayload($classroom, [
                'student_number' => 'ENR-RETIRED-ABSENCE',
                'absences_count' => 999,
            ]))
            ->assertRedirect(route('students.index'));

        $student = Student::where('student_number', 'ENR-RETIRED-ABSENCE')->firstOrFail();
        $this->assertArrayNotHasKey('absences_count', $student->getAttributes());

        $this->actingAs($admin)
            ->put(route('students.update', $student), $this->studentPayload($classroom, [
                'student_number' => $student->student_number,
                'first_name' => 'Updated Safely',
                'absences_count' => 777,
            ]))
            ->assertRedirect(route('students.index'));

        $this->assertSame('Updated Safely', $student->fresh()->first_name);
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_student_creation_is_rejected_clearly_without_an_active_academic_year(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $classroom = Classroom::firstOrFail();
        AcademicYear::query()->update(['is_active' => false]);

        $this->actingAs($admin)
            ->post(route('students.store'), $this->studentPayload($classroom, [
                'student_number' => 'ENR-NO-YEAR',
            ]))
            ->assertSessionHasErrors('academic_year');

        $this->assertDatabaseMissing('students', ['student_number' => 'ENR-NO-YEAR']);
    }

    public function test_classroom_transfer_closes_previous_enrollment_and_synchronizes_current_classroom(): void
    {
        [$oldClassroom, $newClassroom] = Classroom::orderBy('id')->limit(2)->get()->all();
        $student = Student::factory()->create(['classroom_id' => $oldClassroom->id]);
        $oldEnrollment = $student->currentEnrollment()->firstOrFail();
        $academicYear = AcademicYear::active()->firstOrFail();

        $student->updateWithEnrollment(['classroom_id' => $newClassroom->id], $academicYear);

        $this->assertSame($newClassroom->id, $student->fresh()->classroom_id);
        $this->assertNotNull($oldEnrollment->fresh()->left_at);
        $this->assertSame(2, $student->enrollments()->count());
        $this->assertSame(1, $student->enrollments()->active()->count());
        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $student->id,
            'classroom_id' => $newClassroom->id,
            'academic_year_id' => $academicYear->id,
            'left_at' => null,
        ]);
    }

    public function test_same_classroom_in_a_new_academic_year_creates_a_new_enrollment(): void
    {
        $student = Student::factory()->create();
        $oldEnrollment = $student->currentEnrollment()->firstOrFail();
        $newAcademicYear = AcademicYear::factory()->create([
            'name' => '2031-2032',
            'starts_at' => '2031-09-01',
            'ends_at' => '2032-07-31',
        ]);

        $student->updateWithEnrollment([], $newAcademicYear);

        $this->assertNotNull($oldEnrollment->fresh()->left_at);
        $this->assertSame(2, $student->enrollments()->count());
        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $student->id,
            'classroom_id' => $student->classroom_id,
            'academic_year_id' => $newAcademicYear->id,
            'left_at' => null,
        ]);
    }

    public function test_transfer_rolls_back_when_the_target_enrollment_context_is_a_duplicate(): void
    {
        [$oldClassroom, $targetClassroom] = Classroom::orderBy('id')->limit(2)->get()->all();
        $academicYear = AcademicYear::active()->firstOrFail();
        $student = Student::factory()->create(['classroom_id' => $oldClassroom->id]);
        $oldEnrollment = $student->currentEnrollment()->firstOrFail();
        StudentEnrollment::create([
            'student_id' => $student->id,
            'classroom_id' => $targetClassroom->id,
            'academic_year_id' => $academicYear->id,
            'enrolled_at' => now()->subDay()->toDateString(),
            'left_at' => now()->toDateString(),
        ]);

        try {
            $student->updateWithEnrollment(['classroom_id' => $targetClassroom->id], $academicYear);
            $this->fail('The enrollment context unique constraint should reject the transfer.');
        } catch (QueryException) {
            // The transaction must restore both the current classroom and active enrollment.
        }

        $this->assertSame($oldClassroom->id, $student->fresh()->classroom_id);
        $this->assertNull($oldEnrollment->fresh()->left_at);
        $this->assertSame(1, $student->enrollments()->active()->count());
    }

    public function test_model_rejects_a_second_active_enrollment_and_impossible_dates(): void
    {
        $student = Student::factory()->create();
        $otherClassroom = Classroom::whereKeyNot($student->classroom_id)->firstOrFail();
        $otherYear = AcademicYear::factory()->create([
            'name' => '2032-2033',
            'starts_at' => '2032-09-01',
            'ends_at' => '2033-07-31',
        ]);

        try {
            StudentEnrollment::create([
                'student_id' => $student->id,
                'classroom_id' => $otherClassroom->id,
                'academic_year_id' => $otherYear->id,
                'enrolled_at' => now()->toDateString(),
            ]);
            $this->fail('A second active enrollment should be rejected.');
        } catch (InvalidArgumentException) {
            $this->assertSame(1, $student->enrollments()->active()->count());
        }

        $this->expectException(InvalidArgumentException::class);
        StudentEnrollment::create([
            'student_id' => $student->id,
            'classroom_id' => $otherClassroom->id,
            'academic_year_id' => $otherYear->id,
            'enrolled_at' => '2032-10-02',
            'left_at' => '2032-10-01',
        ]);
    }

    public function test_historical_enrollment_preserves_old_professor_grade_access_after_transfer(): void
    {
        [$classroomA, $classroomB] = Classroom::orderBy('id')->limit(2)->get()->all();
        $yearA = AcademicYear::active()->firstOrFail();
        $semesterA = Semester::where('academic_year_id', $yearA->id)->where('sequence', 1)->firstOrFail();
        $professorA = $this->professor();
        $student = Student::factory()->create(['classroom_id' => $classroomA->id]);
        $assignmentA = TeachingAssignment::factory()->create([
            'professor_id' => $professorA->id,
            'classroom_id' => $classroomA->id,
            'subject_id' => Subject::factory()->create(['code' => 'HIST-A'])->id,
            'academic_year_id' => $yearA->id,
        ]);
        $gradeA = StudentGrade::factory()->forAssignment($assignmentA, $student, $semesterA)->create([
            'grade' => 13,
        ]);
        $yearB = AcademicYear::factory()->create([
            'name' => '2033-2034',
            'starts_at' => '2033-09-01',
            'ends_at' => '2034-07-31',
        ]);
        $semesterB = Semester::factory()->create([
            'academic_year_id' => $yearB->id,
            'name' => 'Semester 1',
            'sequence' => 1,
            'position' => 1,
            'code' => 'semester_1_hist_b',
        ]);
        $student->updateWithEnrollment(['classroom_id' => $classroomB->id], $yearB);
        $professorB = $this->professor();
        TeachingAssignment::factory()->create([
            'professor_id' => $professorB->id,
            'classroom_id' => $classroomB->id,
            'subject_id' => Subject::factory()->create([
                'code' => 'HIST-B',
                'semester_id' => $semesterB->id,
            ])->id,
            'academic_year_id' => $yearB->id,
        ]);

        $this->assertTrue($professorA->can('update', $gradeA));
        $this->actingAs($professorA)
            ->get(route('teaching-assignments.show', $assignmentA))
            ->assertOk()
            ->assertSee($student->student_number);
        $this->actingAs($professorA)
            ->get(route('student-grades.results', $student))
            ->assertOk();
        $this->actingAs($professorA)->post(route('student-grades.store'), [
            'teaching_assignment_id' => $assignmentA->id,
            'semester_id' => $semesterA->id,
            'grades' => [[
                'student_id' => $student->id,
                'grade' => 17,
                'coefficient' => 1,
            ]],
        ])->assertRedirect();

        $this->assertSame($classroomB->id, $student->fresh()->classroom_id);
        $this->assertDatabaseHas('student_grades', ['id' => $gradeA->id, 'grade' => 17]);
        $this->assertFalse($professorB->can('update', $gradeA->fresh()));
        $this->actingAs($professorB)->post(route('student-grades.store'), [
            'teaching_assignment_id' => $assignmentA->id,
            'semester_id' => $semesterA->id,
            'grades' => [[
                'student_id' => $student->id,
                'grade' => 19,
                'coefficient' => 1,
            ]],
        ])->assertForbidden();
    }

    public function test_enrollment_from_another_academic_year_does_not_satisfy_assignment_context(): void
    {
        [$classroomA, $classroomB] = Classroom::orderBy('id')->limit(2)->get()->all();
        $yearA = AcademicYear::active()->firstOrFail();
        $semesterA = Semester::where('academic_year_id', $yearA->id)->where('sequence', 1)->firstOrFail();
        $student = Student::factory()->create(['classroom_id' => $classroomA->id]);
        $yearB = AcademicYear::factory()->create([
            'name' => '2034-2035',
            'starts_at' => '2034-09-01',
            'ends_at' => '2035-07-31',
        ]);
        $student->updateWithEnrollment(['classroom_id' => $classroomB->id], $yearB);
        $professor = $this->professor();
        $assignment = TeachingAssignment::factory()->create([
            'professor_id' => $professor->id,
            'classroom_id' => $classroomB->id,
            'subject_id' => Subject::factory()->create(['code' => 'YEAR-ISOLATION'])->id,
            'academic_year_id' => $yearA->id,
        ]);

        $this->actingAs($professor)->post(route('student-grades.store'), [
            'teaching_assignment_id' => $assignment->id,
            'semester_id' => $semesterA->id,
            'grades' => [[
                'student_id' => $student->id,
                'grade' => 15,
                'coefficient' => 1,
            ]],
        ])->assertForbidden();

        $this->assertDatabaseMissing('student_grades', [
            'student_id' => $student->id,
            'teaching_assignment_id' => $assignment->id,
        ]);
    }

    public function test_student_enrollment_schema_contains_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('student_enrollments', [
            'id',
            'student_id',
            'classroom_id',
            'academic_year_id',
            'enrolled_at',
            'left_at',
            'created_at',
            'updated_at',
        ]));
    }

    private function professor(): User
    {
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);

        return $professor;
    }

    private function studentPayload(Classroom $classroom, array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'Enrollment',
            'first_name' => 'Student',
            'student_number' => 'ENR-DEFAULT',
            'classroom_id' => $classroom->id,
            'phone' => '+212600000099',
            'email' => 'enrollment@example.com',
            'diploma' => 'Technician Diploma',
            'city' => 'Casablanca',
            'address' => 'Enrollment test address',
            'education_level' => 'Bac +2',
            'height' => 170,
            'weight' => 70,
        ], $overrides);
    }
}
