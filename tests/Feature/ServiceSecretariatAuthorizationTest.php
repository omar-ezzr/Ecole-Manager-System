<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Department;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceSecretariatAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_secretariat_can_view_hierarchy_but_cannot_write_it(): void
    {
        $secretary = User::factory()->create();
        $secretary->assignRole(Role::ROLE_SECRETARY);
        $school = School::firstOrFail();
        $department = Department::firstOrFail();
        $classroom = Classroom::firstOrFail();

        $this->actingAs($secretary)->get(route('schools.index'))->assertOk();
        $this->actingAs($secretary)->get(route('departments.index'))->assertOk();
        $this->actingAs($secretary)->get(route('classrooms.index'))->assertOk();

        $this->actingAs($secretary)->post(route('schools.store'), $this->schoolPayload())->assertForbidden();
        $this->actingAs($secretary)->put(route('schools.update', $school), $this->schoolPayload())->assertForbidden();
        $this->actingAs($secretary)->delete(route('schools.destroy', $school))->assertForbidden();

        $this->actingAs($secretary)->post(route('departments.store'), $this->departmentPayload($school))->assertForbidden();
        $this->actingAs($secretary)->put(route('departments.update', $department), $this->departmentPayload($school))->assertForbidden();
        $this->actingAs($secretary)->delete(route('departments.destroy', $department))->assertForbidden();

        $this->actingAs($secretary)->post(route('classrooms.store'), $this->classroomPayload($department))->assertForbidden();
        $this->actingAs($secretary)->put(route('classrooms.update', $classroom), $this->classroomPayload($department))->assertForbidden();
        $this->actingAs($secretary)->delete(route('classrooms.destroy', $classroom))->assertForbidden();
    }

    private function schoolPayload(): array
    {
        return ['name' => 'Restricted School', 'country' => 'Morocco', 'region' => 'Region', 'city' => 'City', 'address' => 'Address'];
    }

    private function departmentPayload(School $school): array
    {
        return ['name' => 'Restricted Department', 'school_id' => $school->id, 'address' => 'Address'];
    }

    private function classroomPayload(Department $department): array
    {
        return ['name' => 'Restricted Classroom', 'department_id' => $department->id, 'address' => 'Address'];
    }
}
