<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoStudentSeeder extends Seeder
{
    public function run(): void
    {
        $classrooms = Classroom::orderBy('name')->get();

        if ($classrooms->isEmpty()) {
            return;
        }

        $academicYear = AcademicYear::active()->first();

        if (! $academicYear) {
            throw new RuntimeException('Cannot seed students because no academic year is active.');
        }

        foreach ($this->students() as $index => $student) {
            $classroom = $classrooms[$index % $classrooms->count()];

            $attributes = [
                'student_number' => $student['student_number'],
                'classroom_id' => $classroom->id,
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'phone' => $student['phone'],
                'email' => $student['email'],
                'diploma' => $student['diploma'],
                'city' => $student['city'],
                'address' => $student['address'],
                'education_level' => $student['education_level'],
                'height' => $student['height'],
                'weight' => $student['weight'],
                'appreciation_score' => $student['appreciation_score'],
                'appreciation' => $student['appreciation'],
            ];
            $existingStudent = Student::where('student_number', $student['student_number'])->first();

            if ($existingStudent) {
                $existingStudent->updateWithEnrollment($attributes, $academicYear);
            } else {
                Student::createWithEnrollment($attributes, $academicYear);
            }
        }
    }

    private function students(): array
    {
        $names = [
            ['Amina', 'Bennani'], ['Youssef', 'El Amrani'], ['Salma', 'Tazi'], ['Omar', 'Mansouri'],
            ['Nadia', 'Fassi'], ['Karim', 'Idrissi'], ['Sara', 'Lahlou'], ['Mehdi', 'Rami'],
            ['Imane', 'Ziani'], ['Hamza', 'Alaoui'], ['Lina', 'Kabbaj'], ['Adam', 'Berrada'],
            ['Meryem', 'Slaoui'], ['Anas', 'Haddad'], ['Hajar', 'Naciri'], ['Bilal', 'Cherkaoui'],
            ['Aya', 'Sabri'], ['Ilyas', 'Mekki'], ['Rania', 'Bouzid'], ['Taha', 'Najjar'],
        ];

        return array_map(function (array $name, int $index) {
            $number = $index + 1;

            return [
                'student_number' => 'STD-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                'first_name' => $name[0],
                'last_name' => $name[1],
                'phone' => '+21260000'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                'email' => strtolower($name[0].'.'.$name[1].'@demo-school.local'),
                'diploma' => $number % 2 === 0 ? 'Technician Diploma' : 'Professional Diploma',
                'city' => ['Casablanca', 'Rabat', 'Marrakech', 'Fes'][$index % 4],
                'address' => ($number + 10).' Demo Street',
                'education_level' => ['Bac', 'Bac +2', 'Bac +3'][$index % 3],
                'height' => 160 + ($index % 22),
                'weight' => 55 + ($index % 18),
                'appreciation_score' => round(12 + ($index % 8) + (($index % 3) * 0.25), 2),
                'appreciation' => $index % 4 === 0
                    ? 'Needs regular follow-up on attendance.'
                    : 'Good progress and active participation.',
            ];
        }, $names, array_keys($names));
    }
}
