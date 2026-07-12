<?php

namespace Database\Seeders;

use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use Illuminate\Database\Seeder;

class DemoGradeSeeder extends Seeder
{
    public function run(): void
    {
        $semesters = Semester::orderBy('position')->get();
        $students = Student::where('student_number', 'like', 'STD-%')->orderBy('student_number')->get();

        foreach ($students as $studentIndex => $student) {
            foreach ($semesters as $semester) {
                $grade = round(10 + (($studentIndex + $semester->position) % 9) + ($semester->position * 0.15), 2);

                StudentGrade::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'semester_id' => $semester->id,
                        'subject_id' => null,
                    ],
                    [
                        'grade' => min($grade, 19.5),
                        'appreciation' => $grade >= 14 ? 'Satisfactory semester result.' : 'Additional support recommended.',
                    ]
                );
            }
        }
    }
}
