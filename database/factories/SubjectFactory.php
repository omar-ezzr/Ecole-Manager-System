<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Mathematics',
            'Physics',
            'English',
            'History',
            'Biology',
            'Chemistry',
            'Programming',
        ]);

        return [
            'name' => $name,
            'code' => 'SUB-'.fake()->unique()->numerify('######'),
            'description' => fake()->sentence(),
            'is_active' => true,
            'semester_id' => null,
        ];
    }
}
