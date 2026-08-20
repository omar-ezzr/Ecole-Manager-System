<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_date_must_precede_end_date(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->actingAs($admin)->post(route('academic-years.store'), [
            'name' => '2027-2028',
            'starts_at' => '2027-09-01',
            'ends_at' => '2027-08-31',
            'is_active' => 0,
        ])->assertSessionHasErrors('ends_at');
    }

    public function test_only_one_active_academic_year_is_preserved(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $currentActive = AcademicYear::active()->firstOrFail();

        $this->actingAs($admin)->post(route('academic-years.store'), [
            'name' => '2027-2028',
            'starts_at' => '2027-09-01',
            'ends_at' => '2028-07-31',
            'is_active' => 1,
        ])->assertRedirect(route('academic-years.index'));

        $this->assertFalse($currentActive->fresh()->is_active);
        $this->assertSame(1, AcademicYear::where('is_active', true)->count());
    }

    public function test_director_and_secretariat_have_read_only_academic_year_access(): void
    {
        $academicYear = AcademicYear::firstOrFail();
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);
        $secretary = User::factory()->create();
        $secretary->assignRole(Role::ROLE_SECRETARY);

        foreach ([$director, $secretary] as $user) {
            $this->actingAs($user)->get(route('academic-years.index'))->assertOk();
            $this->actingAs($user)->get(route('academic-years.show', $academicYear))->assertOk();
            $this->actingAs($user)->post(route('academic-years.store'), [])->assertForbidden();
            $this->actingAs($user)->delete(route('academic-years.destroy', $academicYear))->assertForbidden();
        }
    }

    public function test_referenced_academic_year_cannot_be_deleted(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $academicYear = AcademicYear::firstOrFail();

        $this->actingAs($admin)->deleteJson(route('academic-years.destroy', $academicYear))->assertConflict();
    }

    public function test_professor_only_sees_academic_years_with_their_assignments(): void
    {
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $assignedYear = AcademicYear::active()->firstOrFail();
        $unrelatedYear = AcademicYear::factory()->create([
            'name' => '2030-2031',
            'starts_at' => '2030-09-01',
            'ends_at' => '2031-07-31',
        ]);
        TeachingAssignment::factory()->create([
            'professor_id' => $professor->id,
            'classroom_id' => Classroom::firstOrFail()->id,
            'subject_id' => Subject::factory()->create(['code' => 'YEAR-SCOPE'])->id,
            'academic_year_id' => $assignedYear->id,
        ]);

        $this->actingAs($professor)
            ->get(route('academic-years.index'))
            ->assertOk()
            ->assertSee($assignedYear->name)
            ->assertDontSee($unrelatedYear->name);

        $this->actingAs($professor)->get(route('academic-years.show', $assignedYear))->assertOk();
        $this->actingAs($professor)->get(route('academic-years.show', $unrelatedYear))->assertForbidden();
    }
}
