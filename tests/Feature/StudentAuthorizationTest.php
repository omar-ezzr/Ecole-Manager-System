<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_view_students_but_cannot_write_students(): void
    {
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);
        $student = Student::firstOrFail();

        $this->actingAs($director)->get(route('students.index'))->assertOk();
        $this->actingAs($director)->get(route('students.show', $student))->assertOk();
        $this->actingAs($director)->post(route('students.store'), [])->assertForbidden();
        $this->actingAs($director)->put(route('students.update', $student), [])->assertForbidden();
        $this->actingAs($director)->delete(route('students.destroy', $student))->assertForbidden();
    }

    public function test_professor_can_manage_only_assignment_backed_grades_for_assigned_students(): void
    {
        [$assignedClassroom, $otherClassroom] = Classroom::orderBy('id')->limit(2)->get()->all();
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);

        $assignedStudent = Student::factory()->create(['classroom_id' => $assignedClassroom->id]);
        $otherStudent = Student::factory()->create(['classroom_id' => $otherClassroom->id]);
        $academicYear = AcademicYear::active()->firstOrFail();
        $semester = Semester::where('academic_year_id', $academicYear->id)->firstOrFail();
        $assignment = TeachingAssignment::factory()->create([
            'professor_id' => $professor->id,
            'classroom_id' => $assignedClassroom->id,
            'subject_id' => Subject::factory()->create(['code' => 'AUTH-SCOPE'])->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $assignedGrade = StudentGrade::factory()->forAssignment($assignment, $assignedStudent, $semester)->create();
        $legacyAverage = StudentGrade::factory()->create(['student_id' => $assignedStudent->id]);
        $otherGrade = StudentGrade::factory()->create(['student_id' => $otherStudent->id]);

        $this->assertTrue($professor->can('update', $assignedGrade));
        $this->assertFalse($professor->can('update', $legacyAverage));
        $this->assertFalse($professor->can('update', $otherGrade));
        $this->actingAs($professor)->get(route('students.show', $assignedStudent))->assertOk();
        $this->actingAs($professor)->get(route('students.show', $otherStudent))->assertForbidden();
    }

    public function test_service_secretariat_student_access_is_read_only(): void
    {
        $secretary = User::factory()->create();
        $secretary->assignRole(Role::ROLE_SECRETARY);
        $student = Student::firstOrFail();

        $this->actingAs($secretary)
            ->post(route('students.store'), $this->studentPayload(['student_number' => 'SEC-001']))
            ->assertForbidden();

        $this->actingAs($secretary)
            ->put(route('students.update', $student), $this->studentPayload(['student_number' => $student->student_number]))
            ->assertForbidden();

        $this->actingAs($secretary)->delete(route('students.destroy', $student))->assertForbidden();
    }

    public function test_student_can_view_only_their_own_record(): void
    {
        $ownStudent = Student::factory()->create(['classroom_id' => Classroom::firstOrFail()->id]);
        $otherStudent = Student::factory()->create(['classroom_id' => Classroom::firstOrFail()->id]);
        $user = User::factory()->create(['student_id' => $ownStudent->id]);
        $user->assignRole(Role::ROLE_STUDENT);

        $this->actingAs($user)->get(route('students.show', $ownStudent))->assertOk();
        $this->actingAs($user)->get(route('students.show', $otherStudent))->assertForbidden();
        $this->actingAs($user)->put(route('students.update', $ownStudent), [])->assertForbidden();
    }

    private function studentPayload(array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'Secretariat',
            'first_name' => 'Student',
            'student_number' => 'SEC-DEFAULT',
            'classroom_id' => Classroom::firstOrFail()->id,
            'phone' => '+212600000002',
            'email' => 'secretariat@example.com',
            'diploma' => 'Technician Diploma',
            'city' => 'Casablanca',
            'address' => 'Test address',
            'education_level' => 'Bac +2',
            'height' => 170,
            'weight' => 70,
        ], $overrides);
    }
}
