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

class StudentResultsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_open_own_results_and_report_card_only(): void
    {
        [$student, $semester] = $this->studentWithAssignmentGrade();
        $user = User::factory()->create(['student_id' => $student->id]);
        $user->assignRole(Role::ROLE_STUDENT);
        $otherStudent = Student::factory()->create(['classroom_id' => $student->classroom_id]);

        $this->actingAs($user)->get(route('student-grades.results', $student))->assertOk();
        $this->actingAs($user)->get(route('student-grades.report-card', [$student, $semester]))->assertOk();
        $this->actingAs($user)->get(route('student-grades.results', $otherStudent))->assertForbidden();
        $this->actingAs($user)->get(route('student-grades.report-card', [$otherStudent, $semester]))->assertForbidden();
    }

    public function test_director_can_inspect_results_and_report_cards(): void
    {
        [$student, $semester] = $this->studentWithAssignmentGrade();
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($director)->get(route('student-grades.results', $student))->assertOk();
        $this->actingAs($director)->get(route('student-grades.report-card', [$student, $semester]))->assertOk();
    }

    private function studentWithAssignmentGrade(): array
    {
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $academicYear = AcademicYear::active()->firstOrFail();
        $classroom = Classroom::firstOrFail();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create(['code' => 'RES'.$student->id]);
        $assignment = TeachingAssignment::factory()->create([
            'professor_id' => $professor->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $semester = Semester::where('academic_year_id', $academicYear->id)->where('sequence', 1)->firstOrFail();

        StudentGrade::factory()->forAssignment($assignment, $student, $semester)->create();

        return [$student, $semester];
    }
}
