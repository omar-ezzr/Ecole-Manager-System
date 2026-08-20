<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\HealthRecord;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfessorGradeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_professor_can_grade_student_in_assigned_classroom(): void
    {
        [$assignment, $semester, $student, $professor] = $this->assignmentScenario();

        $this->actingAs($professor)->post(route('student-grades.store'), [
            'teaching_assignment_id' => $assignment->id,
            'semester_id' => $semester->id,
            'grades' => [[
                'student_id' => $student->id,
                'grade' => 15.5,
                'type' => 'Exam',
                'coefficient' => 2,
            ]],
        ])->assertRedirect(route('teaching-assignments.show', ['teaching_assignment' => $assignment->id, 'semester_id' => $semester->id]));

        $this->assertDatabaseHas('student_grades', [
            'student_id' => $student->id,
            'teaching_assignment_id' => $assignment->id,
            'semester_id' => $semester->id,
            'grade' => 15.5,
        ]);
    }

    public function test_professor_cannot_grade_student_in_another_classroom(): void
    {
        [$assignment, $semester, , $professor] = $this->assignmentScenario();
        $otherStudent = Student::factory()->create(['classroom_id' => Classroom::whereKeyNot($assignment->classroom_id)->firstOrFail()->id]);

        $this->actingAs($professor)->post(route('student-grades.store'), [
            'teaching_assignment_id' => $assignment->id,
            'semester_id' => $semester->id,
            'grades' => [[
                'student_id' => $otherStudent->id,
                'grade' => 13.5,
                'type' => 'Exam',
                'coefficient' => 1,
            ]],
        ])->assertForbidden();
    }

    public function test_professor_cannot_grade_using_another_professors_assignment_or_modify_its_grade(): void
    {
        [$assignment, $semester, $student] = $this->assignmentScenario();
        $otherProfessor = User::factory()->create();
        $otherProfessor->assignRole(Role::ROLE_PROFESSOR);

        $this->actingAs($otherProfessor)->post(route('student-grades.store'), [
            'teaching_assignment_id' => $assignment->id,
            'semester_id' => $semester->id,
            'grades' => [[
                'student_id' => $student->id,
                'grade' => 12.5,
                'type' => 'Exam',
                'coefficient' => 1,
            ]],
        ])->assertForbidden();

        $grade = StudentGrade::factory()->forAssignment($assignment, $student, $semester)->create();

        $this->assertFalse($otherProfessor->can('update', $grade));
    }

    public function test_professor_cannot_use_semester_from_another_academic_year(): void
    {
        [$assignment, , $student, $professor] = $this->assignmentScenario();
        $otherYear = AcademicYear::factory()->create([
            'name' => '2028-2029',
            'starts_at' => '2028-09-01',
            'ends_at' => '2029-07-31',
        ]);
        $otherSemester = Semester::factory()->create([
            'academic_year_id' => $otherYear->id,
            'name' => 'Semester 1',
            'starts_at' => '2028-09-01',
            'ends_at' => '2028-10-31',
            'sequence' => 1,
            'position' => 1,
            'code' => 'semester_1_alt',
        ]);

        $this->actingAs($professor)->post(route('student-grades.store'), [
            'teaching_assignment_id' => $assignment->id,
            'semester_id' => $otherSemester->id,
            'grades' => [[
                'student_id' => $student->id,
                'grade' => 14,
                'type' => 'Exam',
                'coefficient' => 1,
            ]],
        ])->assertForbidden();
    }

    public function test_director_and_secretariat_can_view_grades_but_cannot_modify_them(): void
    {
        [$assignment, $semester, $student] = $this->assignmentScenario();
        StudentGrade::factory()->forAssignment($assignment, $student, $semester)->create();
        $director = User::factory()->create(['student_id' => null]);
        $director->assignRole(Role::ROLE_DIRECTOR);
        $secretary = User::factory()->create(['student_id' => null]);
        $secretary->assignRole(Role::ROLE_SECRETARY);

        foreach ([$director, $secretary] as $user) {
            $this->actingAs($user)->get(route('student-grades.results', $student))->assertOk();
            $this->actingAs($user)->post(route('student-grades.store'), [
                'teaching_assignment_id' => $assignment->id,
                'semester_id' => $semester->id,
                'grades' => [[
                    'student_id' => $student->id,
                    'grade' => 16,
                    'type' => 'Exam',
                    'coefficient' => 1,
                ]],
            ])->assertForbidden();
        }
    }

    public function test_student_can_view_own_grades_but_not_another_students(): void
    {
        [$assignment, $semester, $student] = $this->assignmentScenario();
        StudentGrade::factory()->forAssignment($assignment, $student, $semester)->create();
        $studentUser = User::factory()->create(['student_id' => $student->id]);
        $studentUser->assignRole(Role::ROLE_STUDENT);
        $otherStudent = Student::factory()->create(['classroom_id' => $student->classroom_id]);

        $this->actingAs($studentUser)->get(route('student-grades.results', $student))->assertOk();
        $this->actingAs($studentUser)->get(route('student-grades.results', $otherStudent))->assertForbidden();
    }

    public function test_operational_administrator_retains_full_grade_management(): void
    {
        [$assignment, $semester, $student] = $this->assignmentScenario();
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->actingAs($admin)->post(route('student-grades.store'), [
            'teaching_assignment_id' => $assignment->id,
            'semester_id' => $semester->id,
            'grades' => [[
                'student_id' => $student->id,
                'grade' => 18,
                'type' => 'Exam',
                'coefficient' => 2,
            ]],
        ])->assertRedirect();

        $grade = StudentGrade::query()
            ->where('student_id', $student->id)
            ->where('teaching_assignment_id', $assignment->id)
            ->where('semester_id', $semester->id)
            ->firstOrFail();

        $this->assertTrue($admin->can('update', $grade));
    }

    public function test_professor_student_view_hides_health_records_and_unassigned_legacy_averages(): void
    {
        [, , $student, $professor] = $this->assignmentScenario();
        HealthRecord::create([
            'student_id' => $student->id,
            'date' => '2026-02-01',
            'type' => 'Restricted consultation',
            'medical_prescription' => 'PRIVATE PROFESSOR HEALTH DATA',
        ]);
        StudentGrade::factory()->create([
            'student_id' => $student->id,
            'grade' => 19.99,
            'teaching_assignment_id' => null,
            'subject_id' => null,
        ]);

        $this->actingAs($professor)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertDontSee('PRIVATE PROFESSOR HEALTH DATA')
            ->assertDontSee('Semester Grades');
    }

    private function assignmentScenario(): array
    {
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $academicYear = AcademicYear::active()->firstOrFail();
        $classroom = Classroom::firstOrFail();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create(['code' => 'SCI'.$professor->id]);
        $assignment = TeachingAssignment::factory()->create([
            'professor_id' => $professor->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $semester = Semester::where('academic_year_id', $academicYear->id)->where('sequence', 1)->firstOrFail();

        return [$assignment, $semester, $student, $professor];
    }
}
