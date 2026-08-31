<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
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

        if (Semester::query()->whereBetween('position', [1, 2])->count() < 2) {
            $this->call(SemesterSeeder::class);
        }

        $classroomIds = Classroom::query()->orderBy('id')->pluck('id')->values();
        $semesters = Semester::query()
            ->whereBetween('position', [1, 2])
            ->orderBy('position')
            ->get();

        if ($classroomIds->isEmpty()) {
            throw new RuntimeException('Cannot seed fake students because no classrooms exist.');
        }

        if ($semesters->count() < 2) {
            throw new RuntimeException('Cannot seed semester grades because semesters 1 and 2 do not exist.');
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
                'appreciation',
                'updated_at',
            ]
        );

        $students = Student::query()
            ->whereIn('student_number', collect($studentRows)->pluck('student_number'))
            ->get()
            ->keyBy('student_number');
        $academicYear = AcademicYear::active()->first();

        if (! $academicYear) {
            throw new RuntimeException('Cannot seed student enrollments because no academic year is active.');
        }

        $this->syncEnrollments($students->values(), $academicYear->id, $timestamp);

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

    private function syncEnrollments($students, int $academicYearId, $timestamp): void
    {
        $studentIds = $students->modelKeys();
        $existingByStudent = DB::table('student_enrollments')
            ->whereIn('student_id', $studentIds)
            ->get()
            ->groupBy('student_id');
        $closeRows = [];
        $newRows = [];

        foreach ($students as $student) {
            $existing = $existingByStudent->get($student->id, collect());
            $active = $existing->whereNull('left_at');

            if ($active->count() > 1) {
                throw new RuntimeException("Student {$student->student_number} has multiple active enrollments.");
            }

            $current = $active->first();

            if ($current
                && (int) $current->classroom_id === (int) $student->classroom_id
                && (int) $current->academic_year_id === $academicYearId) {
                continue;
            }

            if ($existing->contains(fn (object $enrollment) => (int) $enrollment->classroom_id === (int) $student->classroom_id
                && (int) $enrollment->academic_year_id === $academicYearId
            )) {
                throw new RuntimeException("Student {$student->student_number} already has a closed enrollment for the seeded context.");
            }

            if ($current) {
                $closeRows[] = [
                    'id' => $current->id,
                    'left_at' => max($timestamp->toDateString(), $current->enrolled_at),
                    'updated_at' => $timestamp,
                ];
            }

            $newRows[] = [
                'student_id' => $student->id,
                'classroom_id' => $student->classroom_id,
                'academic_year_id' => $academicYearId,
                'enrolled_at' => $timestamp->toDateString(),
                'left_at' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::transaction(function () use ($closeRows, $newRows): void {
            collect($closeRows)
                ->groupBy('left_at')
                ->each(fn ($rows, string $leftAt) => DB::table('student_enrollments')
                    ->whereIn('id', $rows->pluck('id'))
                    ->update([
                        'left_at' => $leftAt,
                        'updated_at' => $rows->first()['updated_at'],
                    ]));

            foreach (array_chunk($newRows, 500) as $chunk) {
                DB::table('student_enrollments')->insert($chunk);
            }
        });
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
