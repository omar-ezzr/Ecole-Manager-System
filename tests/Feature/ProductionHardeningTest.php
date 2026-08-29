<?php

namespace Tests\Feature;

use App\Models\HealthRecord;
use App\Models\Role;
use App\Models\User;
use App\Support\ExcelImportReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_authenticated_user_cannot_continue_sensitive_access(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_api_registration_route_remains_unavailable(): void
    {
        $this->post('/api/auth/register', [
            'name' => 'Public API User',
            'email' => 'api-public@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'api-public@example.com']);
        $this->assertFalse(Route::has('api.auth.register'));
    }

    public function test_corrupt_workbook_is_rejected_cleanly_and_logged_without_workbook_contents(): void
    {
        Log::spy();

        $path = tempnam(sys_get_temp_dir(), 'corrupt-import-').'.xlsx';
        file_put_contents($path, 'not a spreadsheet');

        try {
            (new ExcelImportReader($path))->rows('excel_file', 'students import');
            $this->fail('Expected corrupt workbook validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('excel_file', $exception->errors());
        }

        Log::shouldHaveReceived('warning')
            ->with('Spreadsheet import failed while loading workbook.', \Mockery::on(
                fn (array $context) => ($context['operation'] ?? null) === 'students import'
                    && ($context['field'] ?? null) === 'excel_file'
                    && ! array_key_exists('contents', $context)
            ));
    }

    public function test_workbook_row_limit_is_enforced_before_import_processing(): void
    {
        $reader = new ExcelImportReader($this->xlsxPath([
            ['student_number', 'first_name', 'last_name', 'classroom_id'],
            ['ROW-LIMIT-1', 'Limit', 'One', 1],
            ['ROW-LIMIT-2', 'Limit', 'Two', 1],
        ]));

        $this->expectException(ValidationException::class);

        $reader->rows('excel_file', 'students import', 1);
    }

    public function test_professor_cannot_access_health_record_routes_directly(): void
    {
        $professor = User::factory()->create(['is_active' => true]);
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $record = HealthRecord::firstOrFail();

        $this->actingAs($professor)->get(route('health-records.index'))->assertForbidden();
        $this->actingAs($professor)->get(route('health-records.create'))->assertForbidden();
        $this->actingAs($professor)->post(route('health-records.store'), [])->assertForbidden();
        $this->actingAs($professor)->get(route('health-records.show', $record))->assertForbidden();
        $this->actingAs($professor)->get(route('health-records.edit', $record))->assertForbidden();
        $this->actingAs($professor)->put(route('health-records.update', $record), [])->assertForbidden();
        $this->actingAs($professor)->delete(route('health-records.destroy', $record))->assertForbidden();
    }

    public function test_database_has_high_value_integrity_indexes(): void
    {
        $this->assertTrue(Schema::hasIndex('student_enrollments', 'student_enrollments_unique_context'));
        $this->assertTrue(Schema::hasIndex('student_enrollments', 'student_enrollments_classroom_year_index'));
        $this->assertTrue(Schema::hasIndex('student_grades', 'student_grades_student_semester_assignment_unique'));
        $this->assertTrue(Schema::hasIndex('student_grades', 'student_grades_one_semester_average_unique'));
        $this->assertTrue(Schema::hasIndex('attendance_records', 'attendance_records_enrollment_date_unique'));
    }

    private function xlsxPath(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'hardening-import-test-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}