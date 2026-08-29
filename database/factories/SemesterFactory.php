<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

class SemesterFactory extends Factory
{
    protected $model = Semester::class;

    public function definition(): array
    {
        $academicYear = AcademicYear::query()->inRandomOrder()->first()
            ?? AcademicYear::factory()->create();
        $sequence = fake()->numberBetween(1, 6);
        $start = $academicYear->starts_at->copy()->addDays(($sequence - 1) * 30);
        $end = $sequence === 6 ? $academicYear->ends_at->copy() : $start->copy()->addDays(29);

        return [
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester '.$sequence,
            'code' => 'semester_'.$sequence.'_'.fake()->unique()->numerify('##'),
            'position' => $sequence,
            'starts_at' => $start,
            'ends_at' => $end,
            'sequence' => $sequence,
        ];
    }
}
