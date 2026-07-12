<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use Illuminate\Database\Seeder;
use RuntimeException;

class FakeStudentSemesterSeeder extends Seeder
{
    public function run(): void
    {
        if (Classroom::query()->doesntExist()) {
            $this->call(SchoolStructureSeeder::class);
        }

        if (Semester::query()->whereBetween('position', [1, 6])->count() < 6) {
            $this->call(SemesterSeeder::class);
        }

        $classroomIds = Classroom::query()->orderBy('id')->pluck('id')->values();
        $semesters = Semester::query()
            ->whereBetween('position', [1, 6])
            ->orderBy('position')
            ->get();

        if ($classroomIds->isEmpty()) {
            throw new RuntimeException('Cannot seed fake students because no classrooms exist.');
        }

        if ($semesters->count() < 6) {
            throw new RuntimeException('Cannot seed semester grades because semesters 1 through 6 do not exist.');
        }

        foreach (range(1, 500) as $index) {
            $studentNumber = 'STD-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT);
            $classroomId = $classroomIds[($index - 1) % $classroomIds->count()];
            $studentAttributes = Student::factory()->make([
                'student_number' => $studentNumber,
                'classroom_id' => $classroomId,
                'email' => 'student'.$index.'@demo-school.local',
            ])->getAttributes();

            $student = Student::query()->updateOrCreate(
                ['student_number' => $studentNumber],
                $studentAttributes
            );

            foreach ($semesters as $semester) {
                $grade = $this->gradeFor($index, $semester->position);
                $gradeAttributes = StudentGrade::factory()->make([
                    'student_id' => $student->id,
                    'semester_id' => $semester->id,
                    'subject_id' => null,
                    'grade' => $grade,
                    'appreciation' => $this->appreciationFor($grade),
                ])->getAttributes();

                StudentGrade::query()->updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'semester_id' => $semester->id,
                        'subject_id' => null,
                    ],
                    $gradeAttributes
                );
            }
        }
    }

    private function gradeFor(int $studentIndex, int $semesterPosition): float
    {
        $base = 9 + (($studentIndex * 7 + $semesterPosition * 3) % 10);
        $decimal = (($studentIndex + $semesterPosition) % 4) * 0.25;

        return min(round($base + $decimal, 2), 19.5);
    }

    private function appreciationFor(float $grade): string
    {
        return match (true) {
            $grade >= 16 => 'Excellent semester performance.',
            $grade >= 14 => 'Good semester result.',
            $grade >= 10 => 'Satisfactory semester result.',
            default => 'Additional support recommended.',
        };
    }
}
