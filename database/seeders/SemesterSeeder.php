<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Database\Seeder;

class SemesterSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::active()->firstOrFail();
        $ranges = [
            1 => ['2026-09-01', '2027-01-31'],
            2 => ['2027-02-01', '2027-07-31'],
        ];

        foreach ([1, 2] as $position) {
            Semester::updateOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'sequence' => $position,
                ],
                [
                    'academic_year_id' => $academicYear->id,
                    'name' => 'Semester '.$position,
                    'code' => 'semester_'.$position,
                    'position' => $position,
                    'starts_at' => $ranges[$position][0],
                    'ends_at' => $ranges[$position][1],
                    'sequence' => $position,
                ]
            );
        }
    }
}
