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

class SubjectAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_administrator_can_crud_subjects(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->actingAs($admin)->get(route('subjects.index'))->assertOk();
        $this->actingAs($admin)->get(route('subjects.create'))->assertOk();

        $this->actingAs($admin)->post(route('subjects.store'), [
            'code' => 'GEO',
            'name' => 'Geography',
            'description' => 'Geography core subject',
            'is_active' => 1,
        ])->assertRedirect(route('subjects.index'));

        $subject = Subject::where('code', 'GEO')->firstOrFail();

        $this->actingAs($admin)->put(route('subjects.update', $subject), [
            'code' => 'GEO',
            'name' => 'Advanced Geography',
            'description' => 'Updated description',
            'is_active' => 1,
        ])->assertRedirect(route('subjects.index'));

        $deletable = Subject::factory()->create(['code' => 'DEL001']);

        $this->actingAs($admin)->delete(route('subjects.destroy', $deletable))
            ->assertRedirect(route('subjects.index'));
    }

    public function test_director_and_secretariat_have_read_only_subject_access(): void
    {
        $subject = Subject::firstOrFail();
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);
        $secretary = User::factory()->create();
        $secretary->assignRole(Role::ROLE_SECRETARY);

        foreach ([$director, $secretary] as $user) {
            $this->actingAs($user)->get(route('subjects.index'))->assertOk();
            $this->actingAs($user)->get(route('subjects.show', $subject))->assertOk();
            $this->actingAs($user)->post(route('subjects.store'), [])->assertForbidden();
            $this->actingAs($user)->put(route('subjects.update', $subject), [])->assertForbidden();
            $this->actingAs($user)->delete(route('subjects.destroy', $subject))->assertForbidden();
        }
    }

    public function test_professor_and_student_cannot_manage_subjects(): void
    {
        $subject = Subject::firstOrFail();
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $student = User::factory()->create();
        $student->assignRole(Role::ROLE_STUDENT);

        $this->actingAs($professor)->get(route('subjects.create'))->assertForbidden();
        $this->actingAs($professor)->post(route('subjects.store'), [])->assertForbidden();
        $this->actingAs($student)->get(route('subjects.index'))->assertForbidden();
        $this->actingAs($student)->delete(route('subjects.destroy', $subject))->assertForbidden();
    }

    public function test_duplicate_subject_code_is_rejected(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $subject = Subject::firstOrFail();

        $this->actingAs($admin)->post(route('subjects.store'), [
            'code' => $subject->code,
            'name' => 'Duplicate Code',
            'description' => 'Duplicate code test',
            'is_active' => 1,
        ])->assertSessionHasErrors('code');
    }

    public function test_professor_only_sees_assigned_subjects(): void
    {
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $assignedSubject = Subject::factory()->create(['name' => 'Assigned Subject', 'code' => 'ASSIGNED-SUB']);
        $unrelatedSubject = Subject::factory()->create(['name' => 'Restricted Subject', 'code' => 'OTHER-SUB']);
        TeachingAssignment::factory()->create([
            'professor_id' => $professor->id,
            'classroom_id' => Classroom::firstOrFail()->id,
            'subject_id' => $assignedSubject->id,
            'academic_year_id' => AcademicYear::active()->firstOrFail()->id,
        ]);

        $this->actingAs($professor)
            ->get(route('subjects.index'))
            ->assertOk()
            ->assertSee($assignedSubject->code)
            ->assertDontSee($unrelatedSubject->code);

        $this->actingAs($professor)->get(route('subjects.show', $assignedSubject))->assertOk();
        $this->actingAs($professor)->get(route('subjects.show', $unrelatedSubject))->assertForbidden();
    }
}
