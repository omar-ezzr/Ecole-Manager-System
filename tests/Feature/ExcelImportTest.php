<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Support\ExcelImportReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ExcelImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_import_accepts_supported_classroom_reference(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $classroom = Classroom::firstOrFail();
        $file = $this->xlsx([
            ['student_number', 'first_name', 'last_name', 'classroom_id', 'email', 'phone'],
            ['IMP-001', 'Import', 'Valid', $classroom->id, 'import-valid@example.com', '+212600000003'],
        ]);

        $this->actingAs($admin)
            ->post(route('excel.import'), ['excel_file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('students', [
            'student_number' => 'IMP-001',
            'classroom_id' => $classroom->id,
        ]);
        $student = Student::where('student_number', 'IMP-001')->firstOrFail();
        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => AcademicYear::active()->firstOrFail()->id,
            'left_at' => null,
        ]);
    }

    public function test_retired_absence_column_is_removed_from_students_schema(): void
    {
        $this->assertFalse(Schema::hasColumn('students', 'absences_count'));
    }

    public function test_legacy_absence_headers_are_accepted_but_ignored(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $classroom = Classroom::firstOrFail();

        foreach (['absences_count', 'arrets'] as $index => $legacyHeader) {
            $studentNumber = 'IMP-LEGACY-ABS-'.($index + 1);
            $file = $this->xlsx([
                ['student_number', 'first_name', 'last_name', 'classroom_id', $legacyHeader],
                [$studentNumber, 'Legacy', 'Compatible', $classroom->id, 999],
            ]);

            $this->actingAs($admin)
                ->post(route('excel.import'), ['excel_file' => $file])
                ->assertRedirect()
                ->assertSessionHas('success');

            $student = Student::where('student_number', $studentNumber)->firstOrFail();
            $this->assertDatabaseHas('student_enrollments', [
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'academic_year_id' => AcademicYear::active()->firstOrFail()->id,
                'left_at' => null,
            ]);
        }

        $this->assertSame('absences_count', (new ExcelImportReader(__FILE__))->canonicalHeader('arrets'));
        $this->assertSame(0, AttendanceRecord::query()->whereHas('studentEnrollment.student', fn ($students) => $students
            ->whereIn('student_number', ['IMP-LEGACY-ABS-1', 'IMP-LEGACY-ABS-2']))->count());
    }

    public function test_student_import_rejects_nonexistent_numeric_classroom_id(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $file = $this->xlsx([
            ['student_number', 'first_name', 'last_name', 'classroom_id'],
            ['IMP-002', 'Import', 'Invalid', '999999'],
        ]);

        $this->actingAs($admin)
            ->post(route('excel.import'), ['excel_file' => $file])
            ->assertRedirect()
            ->assertSessionHas('import_errors');

        $this->assertDatabaseMissing('students', ['student_number' => 'IMP-002']);
        $this->assertStringContainsString('invalid classroom_id', session('import_errors')[0]);
    }

    public function test_student_import_requires_an_active_academic_year(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $classroom = Classroom::firstOrFail();
        AcademicYear::query()->update(['is_active' => false]);
        $file = $this->xlsx([
            ['student_number', 'first_name', 'last_name', 'classroom_id'],
            ['IMP-NO-ACTIVE-YEAR', 'Import', 'No Year', $classroom->id],
        ]);

        $this->actingAs($admin)
            ->post(route('excel.import'), ['excel_file' => $file])
            ->assertSessionHasErrors('excel_file');

        $this->assertDatabaseMissing('students', ['student_number' => 'IMP-NO-ACTIVE-YEAR']);
    }

    public function test_student_import_classroom_update_records_a_transfer(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        [$oldClassroom, $newClassroom] = Classroom::orderBy('id')->limit(2)->get()->all();
        $student = Student::factory()->create(['classroom_id' => $oldClassroom->id]);
        $oldEnrollment = $student->currentEnrollment()->firstOrFail();
        $file = $this->xlsx([
            ['student_number', 'first_name', 'last_name', 'classroom_id'],
            [$student->student_number, 'Imported', 'Transfer', $newClassroom->id],
        ]);

        $this->actingAs($admin)
            ->post(route('excel.import'), ['excel_file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($newClassroom->id, $student->fresh()->classroom_id);
        $this->assertNotNull($oldEnrollment->fresh()->left_at);
        $this->assertSame(1, $student->enrollments()->active()->count());
        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $student->id,
            'classroom_id' => $newClassroom->id,
            'academic_year_id' => AcademicYear::active()->firstOrFail()->id,
            'left_at' => null,
        ]);
    }

    public function test_student_import_rejects_text_empty_and_ambiguous_classroom_values(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $classroom = Classroom::firstOrFail();
        Classroom::create(['name' => $classroom->name, 'department_id' => $classroom->department_id]);
        Classroom::create(['name' => '12345', 'department_id' => $classroom->department_id]);

        $file = $this->xlsx([
            ['student_number', 'first_name', 'last_name', 'classroom_id'],
            ['IMP-TEXT', 'Import', 'Text', $classroom->name],
            ['IMP-EMPTY', 'Import', 'Empty', ''],
            ['IMP-NUMERIC-NAME', 'Import', 'Numeric Name', '12345'],
        ]);

        $this->actingAs($admin)
            ->post(route('excel.import'), ['excel_file' => $file])
            ->assertRedirect()
            ->assertSessionHas('import_errors');

        $this->assertDatabaseMissing('students', ['student_number' => 'IMP-TEXT']);
        $this->assertDatabaseMissing('students', ['student_number' => 'IMP-EMPTY']);
        $this->assertDatabaseMissing('students', ['student_number' => 'IMP-NUMERIC-NAME']);
        $this->assertStringContainsString('Row 2: invalid classroom_id', session('import_errors')[0]);
    }

    public function test_legacy_student_import_headers_do_not_map_to_classroom(): void
    {
        $reader = new ExcelImportReader($this->xlsxPath([
            ['compagnie', 'CIE', 'GPT', 'company', 'groupement'],
            ['1', '2', '3', '4', '5'],
        ]));

        foreach (['compagnie', 'CIE', 'GPT', 'company', 'groupement'] as $header) {
            $this->assertNotSame('classroom_id', $reader->canonicalHeader($header));
        }
    }

    public function test_legacy_student_import_headers_do_not_insert_students(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $file = $this->xlsx([
            ['student_number', 'first_name', 'last_name', 'CIE'],
            ['IMP-003', 'Import', 'Legacy', Classroom::firstOrFail()->id],
        ]);

        $this->actingAs($admin)
            ->post(route('excel.import'), ['excel_file' => $file])
            ->assertRedirect()
            ->assertSessionHas('import_errors');

        $this->assertDatabaseMissing('students', ['student_number' => 'IMP-003']);
    }

    public function test_template_download_requires_import_permission(): void
    {
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($director)->get(route('templates.students'))->assertForbidden();
    }

    public function test_oversized_workbook_is_rejected_without_writing_records(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $file = UploadedFile::fake()->create(
            'oversized.xlsx',
            ExcelImportReader::MAX_UPLOAD_KILOBYTES + 1,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $this->actingAs($admin)
            ->post(route('excel.import'), ['excel_file' => $file])
            ->assertSessionHasErrors('excel_file');

        $this->assertDatabaseMissing('students', ['student_number' => 'OVERSIZED']);
    }

    public function test_header_only_and_blank_workbooks_are_rejected(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        foreach ([
            $this->xlsx([['student_number', 'first_name', 'last_name', 'classroom_id']]),
            $this->xlsx([['student_number', 'first_name', 'last_name', 'classroom_id'], ['', ' ', null, '']]),
            $this->xlsx([]),
        ] as $file) {
            $this->actingAs($admin)
                ->post(route('excel.import'), ['excel_file' => $file])
                ->assertSessionHasErrors('excel_file');
        }
    }

    public function test_imported_semester_grades_outside_zero_to_twenty_are_rejected(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $student = Student::firstOrFail();
        $semester = Semester::where('sequence', 1)->whereHas('academicYear', fn ($years) => $years->active())->firstOrFail();
        $file = $this->xlsx([
            ['student_number', 'exam_grade'],
            [$student->student_number, 20.01],
        ]);

        $this->actingAs($admin)
            ->post(route('excel.importSemester1'), ['excel_file_semester_1' => $file])
            ->assertRedirect()
            ->assertSessionHas('import_errors');

        $this->assertStringContainsString('grade must be between 0 and 20', session('import_errors')[0]);
        $this->assertDatabaseMissing('student_grades', [
            'student_id' => $student->id,
            'semester_id' => $semester->id,
            'teaching_assignment_id' => null,
            'subject_id' => null,
            'grade' => 20.01,
        ]);
    }

    private function xlsx(array $rows): UploadedFile
    {
        $path = $this->xlsxPath($rows);

        return new UploadedFile($path, basename($path), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function xlsxPath(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'import-test-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
