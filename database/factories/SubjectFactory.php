<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Mathematics',
            'Physics',
            'English',
            'History',
            'Biology',
            'Chemistry',
            'Programming',
        ]).' '.fake()->unique()->numberBetween(1, 99);

        return [
            'name' => $name,
            'code' => Str::upper(Str::substr(Str::slug($name, ''), 0, 8)),
            'description' => fake()->sentence(),
            'is_active' => true,
            'semester_id' => null,
        ];
    }
}
