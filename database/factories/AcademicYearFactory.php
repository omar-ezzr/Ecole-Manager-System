<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        $startYear = fake()->numberBetween(2024, 2028);

        return [
            'name' => sprintf('%d-%d', $startYear, $startYear + 1),
            'starts_at' => sprintf('%d-09-01', $startYear),
            'ends_at' => sprintf('%d-07-31', $startYear + 1),
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }
}
