<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ExcelTemplateDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracked_templates_exist_and_download_with_current_headers(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        foreach (['students-template.xlsx', 'health-records-template.xlsx'] as $fileName) {
            $this->assertFileExists(resource_path("templates/{$fileName}"));
        }

        $this->actingAs($admin)
            ->get(route('templates.students'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($admin)
            ->get(route('templates.health-records'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->assertSame(
            ['student_number', 'first_name', 'last_name', 'classroom_id', 'phone', 'email', 'diploma', 'city', 'address', 'education_level', 'height', 'weight', 'appreciation_score', 'appreciation'],
            $this->headers(resource_path('templates/students-template.xlsx'))
        );
        $this->assertNotContains('absences_count', $this->headers(resource_path('templates/students-template.xlsx')));
        $this->assertNotContains('arrets', array_map('strtolower', $this->headers(resource_path('templates/students-template.xlsx'))));
        $this->assertSame(
            ['student_number', 'date', 'type', 'medical_prescription'],
            $this->headers(resource_path('templates/health-records-template.xlsx'))
        );

        foreach (['compagnie', 'cie', 'gpt', 'company', 'groupement'] as $legacyHeader) {
            $this->assertNotContains($legacyHeader, array_map('strtolower', $this->headers(resource_path('templates/students-template.xlsx'))));
            $this->assertNotContains($legacyHeader, array_map('strtolower', $this->headers(resource_path('templates/health-records-template.xlsx'))));
        }
    }

    public function test_template_download_requires_import_permission(): void
    {
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($director)->get(route('templates.students'))->assertForbidden();
    }

    private function headers(string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();

        return array_values(array_filter($sheet->rangeToArray('A1:'.$sheet->getHighestColumn().'1')[0], fn ($value) => $value !== null && $value !== ''));
    }
}
