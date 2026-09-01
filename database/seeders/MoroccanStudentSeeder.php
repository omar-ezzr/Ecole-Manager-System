<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

class MoroccanStudentSeeder extends Seeder
{
    private const CLASSROOMS = ['TC-1', 'TC-2', '1BAC-SC-1', '1BAC-SC-2', '2BAC-PC-1', '2BAC-SVT-1'];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Moroccan demo students are only seeded outside production.');

            return;
        }

        $academicYear = AcademicYear::active()->firstOrFail();
        $classrooms = Classroom::query()
            ->whereIn('name', self::CLASSROOMS)
            ->get()
            ->keyBy('name');

        if ($classrooms->count() !== count(self::CLASSROOMS)) {
            throw new RuntimeException('Moroccan demo students require the six demo classrooms.');
        }

        foreach ($this->students($classrooms->map->id->all()) as $attributes) {
            $student = Student::query()
                ->where('student_number', $attributes['student_number'])
                ->first();

            if ($student) {
                $this->syncExistingStudent($student, $attributes, $academicYear);

                continue;
            }

            Student::createWithEnrollment($attributes, $academicYear, $academicYear->starts_at);
        }

        $this->seedSemesterAverages($academicYear);
    }

    /**
     * @param  array<string, int>  $classroomIds
     * @return list<array<string, int|string|float>>
     */
    private function students(array $classroomIds): array
    {
        $firstNames = [
            'Youssef', 'Omar', 'Hamza', 'Anas', 'Ilyas', 'Zakaria', 'Mehdi', 'Ayoub', 'Adam',
            'Amine', 'Soufiane', 'Bilal', 'Ismail', 'Othmane', 'Reda', 'Mohamed', 'Ahmed', 'Karim',
            'Salma', 'Sara', 'Imane', 'Amina', 'Meryem', 'Hajar', 'Aya', 'Lina', 'Ghita', 'Kawtar',
            'Chaimae', 'Nada', 'Ikram', 'Soukaina', 'Rim', 'Asmae', 'Kenza',
        ];
        $lastNames = [
            'Alaoui', 'El Amrani', 'Bennani', 'Berrada', 'Idrissi', 'El Fassi', 'Tazi', 'Lahlou',
            'Kabbaj', 'Benjelloun', 'El Mansouri', 'Naciri', 'Cherkaoui', 'Chraibi', 'Lamrani',
            'Filali', 'Skalli', 'El Alami', 'Bouzidi', 'Amrani', 'Ziani', 'Ouazzani', 'Benkirane',
            'Tahiri', 'El Idrissi', 'Ait Lahcen', 'Bennis', 'Belhaj', 'Kadiri', 'Saidi',
        ];
        $cities = ['Rabat', 'Salé', 'Casablanca', 'Marrakech', 'Fès', 'Meknès', 'Tanger', 'Tétouan', 'Kénitra', 'Agadir'];
        $levels = ['Bac', 'Bac +1', 'Bac +2'];
        $appreciations = [
            'Excellent travail.',
            'Très bon niveau.',
            'Bon travail, continuez ainsi.',
            'Résultats satisfaisants.',
            'Des efforts supplémentaires sont nécessaires.',
        ];
        $districts = ['Hay Riad', 'Agdal', 'Hay Salam', 'Médina', 'Al Qods', 'Al Wifaq'];

        return collect(range(1, 100))->map(function (int $index) use ($firstNames, $lastNames, $cities, $levels, $appreciations, $districts, $classroomIds): array {
            $firstName = $firstNames[($index - 1) % count($firstNames)];
            $lastName = $lastNames[($index - 1) % count($lastNames)];
            $emailFirst = Str::of($firstName)->ascii()->lower();
            $emailLast = Str::of($lastName)->ascii()->lower()->replace(' ', '.');

            return [
                'student_number' => sprintf('STU-%04d', $index),
                'classroom_id' => $classroomIds[self::CLASSROOMS[($index - 1) % count(self::CLASSROOMS)]],
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => '+212600'.str_pad((string) $index, 6, '0', STR_PAD_LEFT),
                'email' => sprintf('%s.%s%02d@ecole.local', $emailFirst, $emailLast, $index),
                'diploma' => 'Baccalauréat',
                'city' => $cities[($index - 1) % count($cities)],
                'address' => sprintf('%d, %s', 10 + $index, $districts[($index - 1) % count($districts)]),
                'education_level' => $levels[($index - 1) % count($levels)],
                'height' => 155 + ($index % 30),
                'weight' => 48 + ($index % 38),
                'appreciation_score' => round(8 + (($index * 7) % 105) / 10, 2),
                'appreciation' => $appreciations[($index - 1) % count($appreciations)],
            ];
        })->all();
    }

    /**
     * @param  array<string, int|string|float>  $attributes
     */
    private function seedSemesterAverages(AcademicYear $academicYear): void
    {
        $semesters = Semester::query()
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('sequence', [1, 2])
            ->orderBy('sequence')
            ->get();

        if ($semesters->count() !== 2) {
            throw new RuntimeException('Moroccan demo grades require Semester 1 and Semester 2.');
        }

        Student::query()
            ->where('student_number', 'like', 'STU-%')
            ->orderBy('student_number')
            ->each(function (Student $student) use ($semesters): void {
                foreach ($semesters as $semester) {
                    $grade = round(8 + (($student->id * 5 + $semester->sequence * 7) % 105) / 10, 2);

                    StudentGrade::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'semester_id' => $semester->id,
                            'teaching_assignment_id' => null,
                            'subject_id' => null,
                        ],
                        [
                            'grade' => $grade,
                            'coefficient' => 1,
                            'appreciation' => $grade >= 14
                                ? 'Très bon semestre.'
                                : ($grade >= 10 ? 'Résultats satisfaisants.' : 'Des efforts supplémentaires sont nécessaires.'),
                        ]
                    );
                }
            });
    }

    /**
     * @param  array<string, int|string|float>  $attributes
     */
    private function syncExistingStudent(Student $student, array $attributes, AcademicYear $academicYear): void
    {
        DB::transaction(function () use ($student, $attributes, $academicYear): void {
            $student = Student::query()->lockForUpdate()->findOrFail($student->id);
            $enrollments = $student->enrollments()->lockForUpdate()->get();
            $activeEnrollments = $enrollments->whereNull('left_at');

            if ($activeEnrollments->count() > 1) {
                throw new LogicException("Student {$student->student_number} has multiple active enrollments.");
            }

            $student->update($attributes);

            $current = $activeEnrollments->first();
            $classroomId = (int) $attributes['classroom_id'];
            $enrolledAt = $academicYear->starts_at->toDateString();

            if ($current
                && $current->classroom_id === $classroomId
                && $current->academic_year_id === $academicYear->id) {
                return;
            }

            if ($current) {
                $current->update([
                    'left_at' => max($enrolledAt, $current->enrolled_at->toDateString()),
                ]);
            }

            $matchingEnrollment = $enrollments->first(fn ($enrollment) => $enrollment->classroom_id === $classroomId
                && $enrollment->academic_year_id === $academicYear->id);

            if ($matchingEnrollment) {
                $matchingEnrollment->update([
                    'enrolled_at' => $enrolledAt,
                    'left_at' => null,
                ]);

                return;
            }

            $student->enrollments()->create([
                'classroom_id' => $classroomId,
                'academic_year_id' => $academicYear->id,
                'enrolled_at' => $enrolledAt,
                'left_at' => null,
            ]);
        });
    }
}
