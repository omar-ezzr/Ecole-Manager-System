<?php

namespace Tests\Feature;

use App\Models\HealthRecord;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthRecordAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_view_health_records_but_cannot_write_them(): void
    {
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);
        $record = HealthRecord::firstOrFail();

        $this->actingAs($director)->get(route('health-records.index'))->assertOk();
        $this->actingAs($director)->get(route('health-records.show', $record))->assertOk();
        $this->actingAs($director)->post(route('health-records.store'), [])->assertForbidden();
        $this->actingAs($director)->put(route('health-records.update', $record), [])->assertForbidden();
        $this->actingAs($director)->delete(route('health-records.destroy', $record))->assertForbidden();
    }

    public function test_service_secretariat_can_view_but_cannot_manage_health_records(): void
    {
        $secretary = User::factory()->create();
        $secretary->assignRole(Role::ROLE_SECRETARY);
        $student = Student::firstOrFail();

        $record = HealthRecord::firstOrFail();

        $this->actingAs($secretary)->get(route('health-records.index'))->assertOk();
        $this->actingAs($secretary)->get(route('health-records.show', $record))->assertOk();
        $this->actingAs($secretary)
            ->post(route('health-records.store'), [
                'student_number' => $student->student_number,
                'date' => '2026-01-10',
                'type' => 'Fievre',
                'medical_prescription' => 'Rest and hydration.',
            ])
            ->assertForbidden();

        $this->actingAs($secretary)->put(route('health-records.update', $record), [])->assertForbidden();
        $this->actingAs($secretary)->delete(route('health-records.destroy', $record))->assertForbidden();

        $this->assertDatabaseMissing('health_records', ['student_id' => $student->id, 'date' => '2026-01-10']);
    }

    public function test_student_cannot_view_another_students_health_record(): void
    {
        $ownStudent = Student::firstOrFail();
        $otherRecord = HealthRecord::where('student_id', '!=', $ownStudent->id)->firstOrFail();
        $user = User::factory()->create(['student_id' => $ownStudent->id]);
        $user->assignRole(Role::ROLE_STUDENT);

        $this->actingAs($user)->get(route('health-records.show', $otherRecord))->assertForbidden();
    }

    public function test_health_record_pages_work_without_removed_columns(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('health-records.index'))
            ->assertOk()
            ->assertDontSee('compagnie_id')
            ->assertDontSee('Compagnie');
    }
}
