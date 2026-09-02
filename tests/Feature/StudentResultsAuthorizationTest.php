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

        $this->actingAs($user)->get(route('student-grades.results', $student))
            ->assertOk()
            ->assertSee(route('students.show', $student), false);
        $this->actingAs($user)->get(route('student-grades.report-card', [$student, $semester]))->assertOk();
        $this->actingAs($user)->get(route('student-grades.results', $otherStudent))->assertForbidden();
        $this->actingAs($user)->get(route('student-grades.report-card', [$otherStudent, $semester]))->assertForbidden();
    }

    public function test_director_can_inspect_results_and_report_cards(): void
    {
        [$student, $semester] = $this->studentWithAssignmentGrade();
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($director)->get(route('student-grades.results', $student))
            ->assertOk()
            ->assertSee(route('students.show', $student), false);
        $this->actingAs($director)->get(route('student-grades.report-card', [$student, $semester]))->assertOk();
    }

    public function test_weighted_results_are_isolated_by_year_and_semester_and_match_report_card(): void
    {
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $classroom = Classroom::firstOrFail();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $firstYear = AcademicYear::active()->firstOrFail();
        $firstSemester = Semester::where('academic_year_id', $firstYear->id)->where('sequence', 1)->firstOrFail();
        $secondSemester = Semester::where('academic_year_id', $firstYear->id)->where('sequence', 2)->firstOrFail();
        $secondYear = AcademicYear::factory()->create([
            'name' => '2030-2031',
            'starts_at' => '2030-09-01',
            'ends_at' => '2031-07-31',
        ]);
        $thirdSemester = Semester::create([
            'academic_year_id' => $secondYear->id,
            'name' => 'Semester 1',
            'code' => 'semester_1_2030',
            'position' => 1,
            'sequence' => 1,
            'starts_at' => '2030-09-01',
            'ends_at' => '2030-12-31',
        ]);
        $fourthSemester = Semester::create([
            'academic_year_id' => $secondYear->id,
            'name' => 'Semester 2',
            'code' => 'semester_2_2031',
            'position' => 2,
            'sequence' => 2,
            'starts_at' => '2031-01-01',
            'ends_at' => '2031-04-30',
        ]);

        $mathematics = $this->assignment($professor, $classroom, $firstYear, 'RESULT-MAT', 'Result Mathematics');
        $physics = $this->assignment($professor, $classroom, $firstYear, 'RESULT-PHY', 'Result Physics');
        $history = $this->assignment($professor, $classroom, $firstYear, 'RESULT-HIS', 'Result History');
        $missing = $this->assignment($professor, $classroom, $firstYear, 'RESULT-MISSING', 'Missing Assignment Grade');
        $nullGrade = $this->assignment($professor, $classroom, $firstYear, 'RESULT-NULL', 'Null Grade');
        $zeroCoefficient = $this->assignment($professor, $classroom, $firstYear, 'RESULT-ZERO', 'Zero Coefficient');
        $futureScience = $this->assignment($professor, $classroom, $secondYear, 'RESULT-FUTURE', 'Future Science');
        $futureLanguage = $this->assignment($professor, $classroom, $secondYear, 'RESULT-LANG', 'Future Language');

        StudentGrade::factory()->forAssignment($mathematics, $student, $firstSemester)->create(['grade' => 10, 'coefficient' => 1]);
        StudentGrade::factory()->forAssignment($physics, $student, $firstSemester)->create(['grade' => 20, 'coefficient' => 3]);
        StudentGrade::factory()->forAssignment($nullGrade, $student, $firstSemester)->create(['grade' => null, 'coefficient' => 5]);
        StudentGrade::factory()->forAssignment($zeroCoefficient, $student, $firstSemester)->create(['grade' => 20, 'coefficient' => 0]);
        StudentGrade::factory()->forAssignment($history, $student, $secondSemester)->create(['grade' => 4, 'coefficient' => 2]);
        StudentGrade::factory()->forAssignment($futureScience, $student, $thirdSemester)->create(['grade' => 6, 'coefficient' => 5]);
        StudentGrade::factory()->forAssignment($futureLanguage, $student, $fourthSemester)->create(['grade' => 14, 'coefficient' => 1]);

        $corruptCrossYearGrade = StudentGrade::factory()->forAssignment($mathematics, $student, $thirdSemester)->create([
            'grade' => 19,
            'coefficient' => 1,
        ]);

        $firstYearResponse = $this->actingAs($director)->get(route('student-grades.results', [
            'student' => $student,
            'academic_year_id' => $firstYear->id,
        ]));

        $firstYearResponse
            ->assertOk()
            ->assertSee('Result Mathematics')
            ->assertSee('Result Physics')
            ->assertSee('Result History')
            ->assertDontSee('Missing Assignment Grade')
            ->assertDontSee('Future Science')
            ->assertDontSee('Future Language')
            ->assertViewHas('semesterResults', function ($results) use ($firstSemester, $secondSemester, $corruptCrossYearGrade): bool {
                $first = $results->firstWhere('semester.id', $firstSemester->id);
                $second = $results->firstWhere('semester.id', $secondSemester->id);

                return $results->count() === 2
                    && $first['average'] === 17.5
                    && $first['grades']->count() === 4
                    && ! $first['grades']->contains('id', $corruptCrossYearGrade->id)
                    && $second['average'] === 4.0
                    && $second['grades']->count() === 1;
            });

        $this->actingAs($director)
            ->get(route('student-grades.report-card', [$student, $firstSemester]))
            ->assertOk()
            ->assertViewHas('weightedAverage', 17.5)
            ->assertViewHas('grades', fn ($grades) => $grades->count() === 4
                && $grades->every(fn (StudentGrade $grade) => $grade->semester_id === $firstSemester->id
                    && $grade->teachingAssignment->academic_year_id === $firstYear->id))
            ->assertDontSee('Future Science');

        $this->actingAs($director)
            ->get(route('student-grades.results', [
                'student' => $student,
                'academic_year_id' => $secondYear->id,
            ]))
            ->assertOk()
            ->assertSee('Future Science')
            ->assertSee('Future Language')
            ->assertDontSee('Result Mathematics')
            ->assertViewHas('semesterResults', fn ($results) => $results->count() === 2
                && $results->firstWhere('semester.id', $thirdSemester->id)['average'] === 6.0
                && $results->firstWhere('semester.id', $fourthSemester->id)['average'] === 14.0);
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

    private function assignment(
        User $professor,
        Classroom $classroom,
        AcademicYear $academicYear,
        string $code,
        string $name
    ): TeachingAssignment {
        $subject = Subject::create([
            'code' => $code,
            'name' => $name,
            'description' => 'Academic result isolation subject.',
            'is_active' => true,
            'semester_id' => null,
        ]);

        return TeachingAssignment::create([
            'professor_id' => $professor->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
        ]);
    }
}
