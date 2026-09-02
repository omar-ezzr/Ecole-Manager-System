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

class AcademicPeriodVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_administrator_sees_seeded_periods_despite_grades_own(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->assertTrue($admin->can('grades.view_own'));

        $this->actingAs($admin)->get(route('academic-years.index'))
            ->assertOk()
            ->assertSee('2026-2027');

        $this->actingAs($admin)->get(route('semesters.index'))
            ->assertOk()
            ->assertSee('Semester 1')
            ->assertSee('Semester 2');
    }

    public function test_director_and_secretariat_see_periods_allowed_by_policy(): void
    {
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);
        $secretary = User::factory()->create();
        $secretary->assignRole(Role::ROLE_SECRETARY);

        foreach ([$director, $secretary] as $user) {
            $this->actingAs($user)->get(route('academic-years.index'))
                ->assertOk()
                ->assertSee('2026-2027');
            $this->actingAs($user)->get(route('semesters.index'))
                ->assertOk()
                ->assertSee('Semester 1')
                ->assertSee('Semester 2');
        }
    }

    public function test_professor_and_student_period_scopes_are_role_based_and_the_year_filter_is_preserved(): void
    {
        $visibleYear = AcademicYear::where('name', '2026-2027')->firstOrFail();
        $visibleSemester = Semester::where('academic_year_id', $visibleYear->id)->where('sequence', 1)->firstOrFail();
        $hiddenYear = AcademicYear::factory()->create([
            'name' => '2033-2034',
            'starts_at' => '2033-09-01',
            'ends_at' => '2034-07-31',
        ]);
        $hiddenSemester = Semester::factory()->create([
            'academic_year_id' => $hiddenYear->id,
            'name' => 'Hidden Semester',
            'code' => 'hidden_semester',
            'position' => 1,
            'sequence' => 1,
            'starts_at' => '2033-09-01',
            'ends_at' => '2034-01-31',
        ]);

        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        TeachingAssignment::factory()->create([
            'professor_id' => $professor->id,
            'classroom_id' => Classroom::firstOrFail()->id,
            'subject_id' => Subject::factory()->create(['code' => 'PERIOD-SCOPE'])->id,
            'academic_year_id' => $visibleYear->id,
        ]);

        $this->actingAs($professor)->get(route('academic-years.index'))
            ->assertOk()
            ->assertSee($visibleYear->name)
            ->assertDontSee($hiddenYear->name);
        $this->actingAs($professor)->get(route('semesters.index'))
            ->assertOk()
            ->assertSee($visibleSemester->name)
            ->assertDontSee($hiddenSemester->name);

        $student = Student::factory()->create();
        $studentUser = User::factory()->create(['student_id' => $student->id]);
        $studentUser->assignRole(Role::ROLE_STUDENT);
        StudentGrade::factory()->create([
            'student_id' => $student->id,
            'semester_id' => $visibleSemester->id,
        ]);

        $this->actingAs($studentUser)->get(route('academic-years.index'))
            ->assertOk()
            ->assertSee($visibleYear->name)
            ->assertDontSee($hiddenYear->name);
        $this->actingAs($studentUser)->get(route('semesters.index'))
            ->assertOk()
            ->assertSee($visibleSemester->name)
            ->assertDontSee($hiddenSemester->name);

        $studentWithoutProfile = User::factory()->create(['student_id' => null]);
        $studentWithoutProfile->assignRole(Role::ROLE_STUDENT);
        $this->actingAs($studentWithoutProfile)->get(route('academic-years.index'))
            ->assertOk()
            ->assertDontSee($visibleYear->name);
        $this->actingAs($studentWithoutProfile)->get(route('semesters.index'))
            ->assertOk()
            ->assertDontSee($visibleSemester->name);

        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $this->actingAs($admin)->get(route('semesters.index', ['academic_year_id' => $visibleYear->id]))
            ->assertOk()
            ->assertSee('Semester 1')
            ->assertSee('Semester 2')
            ->assertDontSee($hiddenSemester->name);
    }
}
