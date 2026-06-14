<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Support\ExcelImportReader;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $reader = ExcelImportReader::fromUploadedFile($request->file('excel_file'));
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($reader->rows() as $row) {
            $rowNumber = $row['_row'] ?? '?';
            $studentNumber = $reader->text($row, 'student_number');
            $firstName = $reader->text($row, 'first_name');
            $lastName = $reader->text($row, 'last_name');
            $classroom = $this->resolveClassroom($reader->text($row, 'classroom_id'));

            if (! $studentNumber || ! $firstName || ! $lastName || ! $classroom) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: missing student number, first name, last name, or valid classroom.";
                continue;
            }

            $student = Student::updateOrCreate(
                ['student_number' => $studentNumber],
                [
                    'classroom_id' => $classroom->id,
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

        return back()
            ->with('success', "Students import finished: {$created} created, {$updated} updated, {$skipped} skipped.")
            ->with('import_errors', $errors);
    }

    private function resolveClassroom(?string $value): ?Classroom
    {
        if ($value === null) {
            return null;
        }

        if (ctype_digit($value)) {
            return Classroom::find((int) $value);
        }

        return Classroom::where('name', $value)->first();
    }

    private function saveSemesterGrades(ExcelImportReader $reader, array $row, Student $student): void
    {
        foreach ([1, 2, 3, 4, 5, 6] as $position) {
            $grade = $reader->decimal($row, 'semester_'.$position);

            if ($grade === null) {
                continue;
            }

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
