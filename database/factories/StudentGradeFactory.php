<?php

namespace Database\Factories;

use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\TeachingAssignment;
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
            'teaching_assignment_id' => null,
            'semester_id' => Semester::query()->inRandomOrder()->value('id'),
            'subject_id' => null,
            'grade' => $grade,
            'type' => 'Exam',
            'coefficient' => 1,
            'appreciation' => $grade >= 14
                ? 'Satisfactory semester result.'
                : 'Additional support recommended.',
        ];
    }

    public function forAssignment(TeachingAssignment $assignment, Student $student, Semester $semester): static
    {
        return $this->state(fn () => [
            'student_id' => $student->id,
            'teaching_assignment_id' => $assignment->id,
            'semester_id' => $semester->id,
            'subject_id' => $assignment->subject_id,
            'type' => 'Exam',
            'coefficient' => 1,
        ]);
    }
}
