<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_output_does_not_expose_removed_domains(): void
    {
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($director)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Compagnie')
            ->assertDontSee('Groupement')
            ->assertDontSee('CIE')
            ->assertDontSee('GPT')
            ->assertDontSee('compagnie_id')
            ->assertDontSee('groupement_id');
    }

    public function test_director_dashboard_is_read_only(): void
    {
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($director)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('View students')
            ->assertDontSee('Add student')
            ->assertDontSee('Create manually')
            ->assertDontSee('Create record');
    }

    public function test_professor_dashboard_does_not_expose_unassigned_classrooms(): void
    {
        [$assignedClassroom, $otherClassroom] = Classroom::orderBy('id')->limit(2)->get()->all();
        $assignedClassroom->update(['name' => 'Professor Dashboard Classroom']);
        $otherClassroom->update(['name' => 'Restricted Dashboard Classroom']);
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        TeachingAssignment::factory()->create([
            'professor_id' => $professor->id,
            'classroom_id' => $assignedClassroom->id,
            'subject_id' => Subject::factory()->create(['code' => 'DASH-SCOPE'])->id,
            'academic_year_id' => AcademicYear::active()->firstOrFail()->id,
        ]);
        Student::factory()->create(['classroom_id' => $assignedClassroom->id]);
        Student::factory()->create(['classroom_id' => $otherClassroom->id]);

        $this->actingAs($professor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($assignedClassroom->name)
            ->assertDontSee($otherClassroom->name);
    }
}
