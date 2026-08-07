<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Support\ExcelImportReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => ExcelImportReader::uploadRules(),
        ]);

        $reader = ExcelImportReader::fromUploadedFile($request->file('excel_file'));
        $rows = $reader->rows();

        if ($rows === []) {
            return back()
                ->withErrors(['excel_file' => 'The uploaded workbook contains no data rows.'])
                ->with('import_errors', ['The uploaded workbook contains no data rows.']);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        $classrooms = Classroom::query()->pluck('id')->flip();

        DB::transaction(function () use ($reader, $rows, $classrooms, &$created, &$updated, &$skipped, &$errors): void {
            foreach ($rows as $row) {
                $rowNumber = $row['_row'] ?? '?';
                $studentNumber = $reader->text($row, 'student_number');
                $firstName = $reader->text($row, 'first_name');
                $lastName = $reader->text($row, 'last_name');
                $classroomReference = $reader->text($row, 'classroom_id');
                $classroomId = $this->resolveClassroomId($classrooms, $classroomReference);

                if (! $studentNumber || ! $firstName || ! $lastName) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: missing student number, first name, or last name.";
                    continue;
                }

                if (! $classroomId) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: invalid classroom_id '{$classroomReference}'. Use an existing numeric classroom ID.";
                    continue;
                }

                $student = Student::updateOrCreate(
                    ['student_number' => $studentNumber],
                    [
                        'classroom_id' => $classroomId,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'phone' => $reader->text($row, 'phone'),
                        'email' => $reader->text($row, 'email'),
                        'diploma' => $reader->text($row, 'diploma'),
                        'city' => $reader->text($row, 'city'),
                        'address' => $reader->text($row, 'address'),
                        'education_level' => $reader->text($row, 'education_level'),
                        'height' => $reader->integer($row, 'height'),
                        'weight' => $reader->integer($row, 'weight'),
                        'appreciation_score' => $reader->decimal($row, 'appreciation_score', 0),
                        'absences_count' => $reader->integer($row, 'absences_count', 0),
                        'appreciation' => $reader->text($row, 'appreciation'),
                    ]
                );

                $student->wasRecentlyCreated ? $created++ : $updated++;
                $this->saveSemesterGrades($reader, $row, $student);
            }
        });

        return back()
            ->with('success', "Students import finished: {$created} created, {$updated} updated, {$skipped} skipped.")
            ->with('import_errors', $errors);
    }

    private function resolveClassroomId($classrooms, ?string $value): ?int
    {
        if ($value === null || ! ctype_digit($value)) {
            return null;
        }

        $id = (int) $value;

        return $classrooms->has($id) ? $id : null;
    }

    private function saveSemesterGrades(ExcelImportReader $reader, array $row, Student $student): void
    {
        foreach ([1, 2, 3, 4, 5, 6] as $position) {
            $grade = $reader->decimal($row, 'semester_'.$position);

            if ($grade === null) {
                continue;
            }

            $this->authorize('createForStudent', [StudentGrade::class, $student]);

            $semester = Semester::where('position', $position)->first();

            if (! $semester) {
                continue;
            }

            StudentGrade::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'semester_id' => $semester->id,
                    'subject_id' => null,
                ],
                ['grade' => $grade]
            );
        }
    }
}
