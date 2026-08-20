<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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

        $timestamp = now();
        $studentRows = [];

        foreach (range(1, 500) as $index) {
            $studentNumber = 'STD-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT);
            $classroomId = $classroomIds[($index - 1) % $classroomIds->count()];
            $studentRows[] = [
                'student_number' => $studentNumber,
                'classroom_id' => $classroomId,
                'first_name' => 'Student'.$index,
                'last_name' => 'Demo',
                'phone' => '+212600'.str_pad((string) $index, 6, '0', STR_PAD_LEFT),
                'email' => 'student'.$index.'@demo-school.local',
                'diploma' => 'Technician Diploma',
                'city' => 'Casablanca',
                'address' => 'Campus Residence '.$index,
                'education_level' => 'Bac +2',
                'height' => 165 + ($index % 15),
                'weight' => 55 + ($index % 20),
                'appreciation_score' => 0,
                'absences_count' => $index % 5,
                'appreciation' => 'Consistent effort throughout the semester.',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        Student::query()->upsert(
            $studentRows,
            ['student_number'],
            [
                'classroom_id',
                'first_name',
                'last_name',
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
                'updated_at',
            ]
        );

        $students = Student::query()
            ->whereIn('student_number', collect($studentRows)->pluck('student_number'))
            ->get()
            ->keyBy('student_number');

        $gradeRows = [];

        foreach (range(1, 500) as $index) {
            $studentNumber = 'STD-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT);
            $student = $students->get($studentNumber);

            foreach ($semesters as $semester) {
                $grade = $this->gradeFor($index, $semester->position);

                $gradeRows[] = [
                    'student_id' => $student->id,
                    'teaching_assignment_id' => null,
                    'semester_id' => $semester->id,
                    'subject_id' => null,
                    'semester_average_slot' => 1,
                    'grade' => $grade,
                    'type' => null,
                    'coefficient' => 1,
                    'appreciation' => $this->appreciationFor($grade),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        DB::table('student_grades')->upsert(
            $gradeRows,
            ['student_id', 'semester_id', 'semester_average_slot'],
            ['grade', 'appreciation', 'updated_at']
        );
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
