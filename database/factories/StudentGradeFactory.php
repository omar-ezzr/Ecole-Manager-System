<?php

namespace Database\Factories;

use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentGrade>
 */
class StudentGradeFactory extends Factory
{
    protected $model = StudentGrade::class;

    public function definition(): array
    {
        $grade = fake()->randomFloat(2, 7, 19.5);

        return [
            'student_id' => Student::query()->inRandomOrder()->value('id'),
            'semester_id' => Semester::query()->inRandomOrder()->value('id'),
            'subject_id' => null,
            'grade' => $grade,
            'appreciation' => $grade >= 14
                ? 'Satisfactory semester result.'
                : 'Additional support recommended.',
        ];
    }
}
