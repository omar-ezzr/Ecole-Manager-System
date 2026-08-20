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

class TeachingAssignmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_administrator_can_create_assignment(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);

        $this->actingAs($admin)->post(route('teaching-assignments.store'), $this->payload($professor))
            ->assertRedirect(route('teaching-assignments.index'));

        $this->assertDatabaseHas('teaching_assignments', [
            'professor_id' => $professor->id,
        ]);
    }

    public function test_director_and_secretariat_cannot_create_update_or_delete_assignment(): void
    {
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $assignment = TeachingAssignment::factory()->create();
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);
        $secretary = User::factory()->create();
        $secretary->assignRole(Role::ROLE_SECRETARY);

        foreach ([$director, $secretary] as $user) {
            $this->actingAs($user)->get(route('teaching-assignments.index'))->assertOk();
            $this->actingAs($user)->get(route('teaching-assignments.show', $assignment))->assertOk();
            $this->actingAs($user)->post(route('teaching-assignments.store'), $this->payload($professor))->assertForbidden();
            $this->actingAs($user)->put(route('teaching-assignments.update', $assignment), $this->payload($professor))->assertForbidden();
            $this->actingAs($user)->delete(route('teaching-assignments.destroy', $assignment))->assertForbidden();
        }
    }

    public function test_professor_can_view_own_assignment_but_not_another_professors_assignment_or_edit_it(): void
    {
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $otherProfessor = User::factory()->create();
        $otherProfessor->assignRole(Role::ROLE_PROFESSOR);
        $ownSubject = Subject::factory()->create(['name' => 'Professor Scope Subject', 'code' => 'OWN-TA']);
        $otherSubject = Subject::factory()->create(['name' => 'Unrelated Scope Subject', 'code' => 'OTHER-TA']);
        $ownAssignment = TeachingAssignment::factory()->create([
            'professor_id' => $professor->id,
            'subject_id' => $ownSubject->id,
        ]);
        $otherAssignment = TeachingAssignment::factory()->create([
            'professor_id' => $otherProfessor->id,
            'subject_id' => $otherSubject->id,
        ]);

        $this->actingAs($professor)
            ->get(route('teaching-assignments.index'))
            ->assertOk()
            ->assertSee($ownSubject->code)
            ->assertDontSee($otherSubject->code)
            ->assertDontSee($otherProfessor->name);
        $this->actingAs($professor)->get(route('teaching-assignments.show', $ownAssignment))->assertOk();
        $this->actingAs($professor)->get(route('teaching-assignments.show', $otherAssignment))->assertForbidden();
        $this->actingAs($professor)->get(route('teaching-assignments.edit', $ownAssignment))->assertForbidden();
    }

    public function test_student_cannot_access_assignment_management(): void
    {
        $student = User::factory()->create();
        $student->assignRole(Role::ROLE_STUDENT);

        $this->actingAs($student)->get(route('teaching-assignments.index'))->assertForbidden();
    }

    public function test_non_professor_user_cannot_be_assigned_as_professor(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($admin)->post(route('teaching-assignments.store'), $this->payload($director))
            ->assertSessionHasErrors('professor_id');
    }

    public function test_duplicate_assignment_is_rejected(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $payload = $this->payload($professor);

        TeachingAssignment::create($payload);

        $this->actingAs($admin)->post(route('teaching-assignments.store'), $payload)
            ->assertSessionHasErrors('professor_id');
    }

    private function payload(User $professor): array
    {
        return [
            'professor_id' => $professor->id,
            'classroom_id' => Classroom::firstOrFail()->id,
            'subject_id' => Subject::firstOrFail()->id,
            'academic_year_id' => AcademicYear::active()->firstOrFail()->id,
        ];
    }
}
