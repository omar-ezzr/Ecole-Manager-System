<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'classroom_id' => Classroom::query()->inRandomOrder()->value('id'),
            'student_number' => 'STD-'.fake()->unique()->numerify('######'),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => '+2126'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'diploma' => fake()->randomElement(['Technician Diploma', 'Professional Diploma', 'Specialized Technician Diploma']),
            'city' => fake()->randomElement(['Casablanca', 'Rabat', 'Marrakech', 'Fes', 'Tangier', 'Agadir', 'Meknes']),
            'address' => fake()->streetAddress(),
            'education_level' => fake()->randomElement(['Bac', 'Bac +2', 'Bac +3', 'Bac +4', 'Bac +5']),
            'height' => fake()->numberBetween(155, 192),
            'weight' => fake()->numberBetween(48, 95),
            'appreciation_score' => fake()->randomFloat(2, 8, 19),
            'absences_count' => fake()->numberBetween(0, 18),
            'appreciation' => fake()->randomElement([
                'Good progress and active participation.',
                'Consistent effort throughout the semester.',
                'Needs regular follow-up on attendance.',
                'Strong academic potential with steady improvement.',
            ]),
        ];
    }
}
