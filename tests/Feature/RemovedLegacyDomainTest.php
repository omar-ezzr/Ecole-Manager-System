<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\HealthRecord;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Support\ExcelImportReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

class RemovedLegacyDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_compagnie_and_groupement_urls_do_not_exist(): void
    {
        $this->get('/compagnies')->assertNotFound();
        $this->get('/groupements')->assertNotFound();
    }

    public function test_legacy_compagnie_and_groupement_named_routes_do_not_exist(): void
    {
        foreach ([
            'compagnies.index',
            'compagnies.create',
            'compagnies.store',
            'compagnies.show',
            'compagnies.edit',
            'compagnies.update',
            'compagnies.destroy',
            'groupements.index',
            'groupements.create',
            'groupements.store',
            'groupements.show',
            'groupements.edit',
            'groupements.update',
            'groupements.destroy',
        ] as $routeName) {
            $this->assertFalse(RouteFacade::has($routeName), "Unexpected legacy route remains: {$routeName}");
        }
    }

    public function test_student_pages_and_writes_do_not_require_compagnie_id(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $classroom = Classroom::firstOrFail();

        $this->actingAs($admin)->get(route('students.index'))->assertOk()->assertDontSee('compagnie_id');
        $this->actingAs($admin)->get(route('students.create'))->assertOk()->assertDontSee('compagnie_id');

        $this->actingAs($admin)
            ->post(route('students.store'), $this->studentPayload($classroom, [
                'student_number' => 'NO-CIE-001',
                'first_name' => 'No',
                'last_name' => 'Company',
            ]))
            ->assertRedirect(route('students.index'));

        $student = Student::where('student_number', 'NO-CIE-001')->firstOrFail();
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'classroom_id' => $classroom->id,
        ]);

        $this->actingAs($admin)->get(route('students.show', $student))->assertOk()->assertDontSee('compagnie_id');

        $this->actingAs($admin)
            ->put(route('students.update', $student), $this->studentPayload($classroom, [
                'student_number' => 'NO-CIE-001',
                'first_name' => 'Updated',
                'last_name' => 'Student',
            ]))
            ->assertRedirect(route('students.index'));

        $this->assertSame('Updated', $student->fresh()->first_name);
    }

    public function test_health_records_do_not_require_compagnie_id(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $student = Student::factory()->create(['classroom_id' => Classroom::firstOrFail()->id]);

        $this->actingAs($admin)->get(route('health-records.index'))->assertOk()->assertDontSee('compagnie_id');

        $this->actingAs($admin)
            ->post(route('health-records.store'), [
                'student_number' => $student->student_number,
                'date' => '2026-07-23',
                'type' => 'Consultation',
                'medical_prescription' => 'Rest and follow-up',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('health_records', [
            'student_id' => $student->id,
            'type' => 'Consultation',
        ]);

        $record = HealthRecord::where('student_id', $student->id)->where('type', 'Consultation')->firstOrFail();
        $this->actingAs($admin)->get(route('health-records.show', $record))->assertOk()->assertDontSee('compagnie_id');
    }

    public function test_dashboard_and_school_pages_do_not_expose_legacy_counters_or_relationships(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $school = School::firstOrFail();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Compagnie')
            ->assertDontSee('Groupement')
            ->assertDontSee('CIE')
            ->assertDontSee('GPT');

        $this->actingAs($admin)
            ->get(route('schools.show', $school))
            ->assertOk()
            ->assertDontSee('groupement_id')
            ->assertDontSee('Groupement');
    }

    public function test_import_reader_no_longer_maps_legacy_headers_to_active_columns(): void
    {
        $reader = new ExcelImportReader(__FILE__);

        foreach (['compagnie', 'compagnie_id', 'cie', 'CIE', 'groupement', 'groupement_id', 'gpt', 'GPT'] as $header) {
            $this->assertSame(strtolower($header), $reader->canonicalHeader($header));
        }

        $this->assertSame('classroom_id', $reader->canonicalHeader('classroom'));
    }

    private function studentPayload(Classroom $classroom, array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'Student',
            'first_name' => 'Example',
            'student_number' => 'STD-LEGACY-001',
            'classroom_id' => $classroom->id,
            'phone' => '+212600000000',
            'email' => 'student-legacy@example.com',
            'diploma' => 'Technician Diploma',
            'city' => 'Casablanca',
            'address' => '123 School Street',
            'education_level' => 'Bac +2',
            'height' => 170,
            'weight' => 70,
            'appreciation_score' => 12,
            'absences_count' => 0,
            'appreciation' => 'Consistent progress.',
        ], $overrides);
    }
}
