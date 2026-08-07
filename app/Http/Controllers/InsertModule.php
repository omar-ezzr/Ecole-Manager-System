<?php

namespace App\Http\Controllers;

use App\Models\HealthRecord;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Support\ExcelImportReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class InsertModule extends Controller
{
    private const IDENTITY_COLUMNS = [
        'student_number',
        'first_name',
        'last_name',
        'classroom_id',
        'phone',
        'email',
        'diploma',
        'city',
        'address',
        'education_level',
        'height',
        'weight',
        'appreciation_score',
        'absences_count',
        'appreciation',
        'date',
        'type',
        'medical_prescription',
    ];

    public function healthRecords(Request $request)
    {
        $request->validate([
            'excel_file_health_records' => ExcelImportReader::uploadRules(),
        ]);

        $reader = ExcelImportReader::fromUploadedFile($request->file('excel_file_health_records'));
        $rows = $reader->rows();

        if ($rows === []) {
            return $this->emptyWorkbookResponse('excel_file_health_records');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($reader, $rows, &$created, &$updated, &$skipped, &$errors): void {
            foreach ($rows as $row) {
                $rowNumber = $row['_row'] ?? '?';
                $studentNumber = $reader->text($row, 'student_number');
                $student = $studentNumber ? Student::where('student_number', $studentNumber)->first() : null;
                $date = $reader->date($row, 'date');
                $type = $reader->text($row, 'type');

                if (! $student || ! $date || ! $type) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: missing valid student number, date, or health record type.";
                    continue;
                }

                $record = HealthRecord::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'date' => $date,
                        'type' => $type,
                    ],
                    [
                        'medical_prescription' => $reader->text($row, 'medical_prescription'),
                    ]
                );

                $record->wasRecentlyCreated ? $created++ : $updated++;
            }
        });

        return back()
            ->with('success', "Health records import finished: {$created} created, {$updated} updated, {$skipped} skipped.")
            ->with('import_errors', $errors);
    }

    public function semester1(Request $request)
    {
        return $this->importSemesterAverage($request, 'excel_file_semester_1', 1);
    }

    public function semester2(Request $request)
    {
        return $this->importSemesterAverage($request, 'excel_file_semester_2', 2);
    }

    public function semester3(Request $request)
    {
        return $this->importSemesterAverage($request, 'excel_file_semester_3', 3);
    }

    public function semester4(Request $request)
    {
        return $this->importSemesterAverage($request, 'excel_file_semester_4', 4);
    }

    public function semesters5And6(Request $request)
    {
        $request->validate([
            'excel_file_semesters_5_6' => ExcelImportReader::uploadRules(),
        ]);

        $reader = ExcelImportReader::fromUploadedFile($request->file('excel_file_semesters_5_6'));
        $rows = $reader->rows();

        if ($rows === []) {
            return $this->emptyWorkbookResponse('excel_file_semesters_5_6');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($reader, $rows, &$created, &$updated, &$skipped, &$errors): void {
            foreach ($rows as $row) {
                $rowNumber = $row['_row'] ?? '?';
                $student = $this->studentFromRow($reader, $row);
                $semester5 = $reader->decimal($row, 'semester_5');
                $semester6 = $reader->decimal($row, 'semester_6');

                if (! $student || ($semester5 === null && $semester6 === null)) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: missing valid student number or semester 5/6 grade.";
                    continue;
                }

                foreach ([5 => $semester5, 6 => $semester6] as $position => $grade) {
                    if ($grade === null) {
                        continue;
                    }

                    $gradeModel = $this->saveSemesterGrade($student, $position, $grade);

                    if ($gradeModel?->wasRecentlyCreated) {
                        $created++;
                    } elseif ($gradeModel) {
                        $updated++;
                    }
                }
            }
        });

        return back()
            ->with('success', "Semesters 5 and 6 import finished: {$created} created, {$updated} updated, {$skipped} skipped.")
            ->with('import_errors', $errors);
    }

    private function importSemesterAverage(Request $request, string $field, int $semesterPosition)
    {
        $request->validate([
            $field => ExcelImportReader::uploadRules(),
        ]);

        $reader = ExcelImportReader::fromUploadedFile($request->file($field));
        $rows = $reader->rows();

        if ($rows === []) {
            return $this->emptyWorkbookResponse($field);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($reader, $rows, $semesterPosition, &$created, &$updated, &$skipped, &$errors): void {
            foreach ($rows as $row) {
                $rowNumber = $row['_row'] ?? '?';
                $student = $this->studentFromRow($reader, $row);
                $average = $reader->averageNumeric($row, null, self::IDENTITY_COLUMNS);

                if (! $student || $average === null) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: missing valid student number or numeric grade columns.";
                    continue;
                }

                $gradeModel = $this->saveSemesterGrade($student, $semesterPosition, $average);

                if (! $gradeModel) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: semester {$semesterPosition} is not configured.";
                    continue;
                }

                $gradeModel->wasRecentlyCreated ? $created++ : $updated++;
            }
        });

        return back()
            ->with('success', "Semester {$semesterPosition} import finished: {$created} created, {$updated} updated, {$skipped} skipped.")
            ->with('import_errors', $errors);
    }

    private function studentFromRow(ExcelImportReader $reader, array $row): ?Student
    {
        $studentNumber = $reader->text($row, 'student_number');

        return $studentNumber ? Student::where('student_number', $studentNumber)->first() : null;
    }

    private function saveSemesterGrade(Student $student, int $semesterPosition, float $grade): ?StudentGrade
    {
        $this->authorize('createForStudent', [StudentGrade::class, $student]);

        $semester = Semester::where('position', $semesterPosition)->first();

        if (! $semester) {
            return null;
        }

        return StudentGrade::updateOrCreate(
            [
                'student_id' => $student->id,
                'semester_id' => $semester->id,
                'subject_id' => null,
            ],
            ['grade' => $grade]
        );
    }

    private function emptyWorkbookResponse(string $field)
    {
        return back()
            ->withErrors([$field => 'The uploaded workbook contains no data rows.'])
            ->with('import_errors', ['The uploaded workbook contains no data rows.']);
    }
}
