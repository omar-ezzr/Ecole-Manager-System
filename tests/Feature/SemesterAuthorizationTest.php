<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SemesterAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_semester_must_belong_within_academic_year_dates(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $academicYear = AcademicYear::active()->firstOrFail();

        $this->actingAs($admin)->post(route('semesters.store'), [
            'academic_year_id' => $academicYear->id,
            'name' => 'Out of Range Semester',
            'starts_at' => '2026-08-01',
            'ends_at' => '2026-08-31',
            'sequence' => 7,
        ])->assertSessionHasErrors('starts_at');
    }

    public function test_director_and_secretariat_have_read_only_semester_access(): void
    {
        $semester = Semester::firstOrFail();
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);
        $secretary = User::factory()->create();
        $secretary->assignRole(Role::ROLE_SECRETARY);

        foreach ([$director, $secretary] as $user) {
            $this->actingAs($user)->get(route('semesters.index'))->assertOk();
            $this->actingAs($user)->get(route('semesters.show', $semester))->assertOk();
            $this->actingAs($user)->post(route('semesters.store'), [])->assertForbidden();
            $this->actingAs($user)->delete(route('semesters.destroy', $semester))->assertForbidden();
        }
    }

    public function test_referenced_semester_cannot_be_deleted(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $semester = Semester::firstOrFail();

        $this->actingAs($admin)->deleteJson(route('semesters.destroy', $semester))->assertConflict();
    }

    public function test_professor_only_sees_semesters_in_assigned_academic_years(): void
    {
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $assignedYear = AcademicYear::active()->firstOrFail();
        $assignedSemester = Semester::where('academic_year_id', $assignedYear->id)->firstOrFail();
        $unrelatedYear = AcademicYear::factory()->create([
            'name' => '2032-2033',
            'starts_at' => '2032-09-01',
            'ends_at' => '2033-07-31',
        ]);
        $unrelatedSemester = Semester::factory()->create([
            'academic_year_id' => $unrelatedYear->id,
            'name' => 'Restricted Semester',
            'code' => 'restricted_semester',
            'position' => 1,
            'sequence' => 1,
            'starts_at' => '2032-09-01',
            'ends_at' => '2032-10-31',
        ]);
        TeachingAssignment::factory()->create([
            'professor_id' => $professor->id,
            'classroom_id' => Classroom::firstOrFail()->id,
            'subject_id' => Subject::factory()->create(['code' => 'SEM-SCOPE'])->id,
            'academic_year_id' => $assignedYear->id,
        ]);

        $this->actingAs($professor)
            ->get(route('semesters.index'))
            ->assertOk()
            ->assertSee($assignedSemester->name)
            ->assertDontSee($unrelatedSemester->name)
            ->assertDontSee($unrelatedYear->name);

        $this->actingAs($professor)->get(route('semesters.show', $assignedSemester))->assertOk();
        $this->actingAs($professor)->get(route('semesters.show', $unrelatedSemester))->assertForbidden();
    }
}
