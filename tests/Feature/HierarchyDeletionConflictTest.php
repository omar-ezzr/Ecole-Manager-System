<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Department;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HierarchyDeletionConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_referenced_parent_deletions_return_conflict_for_json_requests(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $school = School::has('departments')->firstOrFail();
        $department = Department::has('classrooms')->firstOrFail();
        $classroom = Classroom::has('students')->firstOrFail();

        $this->actingAs($admin)->deleteJson(route('schools.destroy', $school))->assertConflict();
        $this->actingAs($admin)->deleteJson(route('departments.destroy', $department))->assertConflict();
        $this->actingAs($admin)->deleteJson(route('classrooms.destroy', $classroom))->assertConflict();
    }

    public function test_unauthorized_parent_deletion_returns_forbidden(): void
    {
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($director)->deleteJson(route('schools.destroy', School::firstOrFail()))->assertForbidden();
        $this->actingAs($director)->deleteJson(route('departments.destroy', Department::firstOrFail()))->assertForbidden();
        $this->actingAs($director)->deleteJson(route('classrooms.destroy', Classroom::firstOrFail()))->assertForbidden();
    }

    public function test_authorized_unreferenced_parent_deletions_succeed(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $school = School::create(['name' => 'Empty School']);
        $department = Department::create(['name' => 'Empty Department', 'school_id' => School::firstOrFail()->id]);
        $classroom = Classroom::create(['name' => 'Empty Classroom', 'department_id' => Department::firstOrFail()->id]);

        $this->actingAs($admin)->delete(route('classrooms.destroy', $classroom))->assertRedirect('classrooms');
        $this->actingAs($admin)->delete(route('departments.destroy', $department))->assertRedirect('departments');
        $this->actingAs($admin)->delete(route('schools.destroy', $school))->assertRedirect('schools');
    }

    public function test_database_fk_still_prevents_race_condition_deletion(): void
    {
        $classroom = Classroom::firstOrFail();
        Student::factory()->create(['classroom_id' => $classroom->id]);

        $this->expectException(QueryException::class);
        $classroom->delete();
    }
}
