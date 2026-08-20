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
            1 => ['2026-09-01', '2026-10-31'],
            2 => ['2026-11-01', '2026-12-31'],
            3 => ['2027-01-01', '2027-02-28'],
            4 => ['2027-03-01', '2027-04-30'],
            5 => ['2027-05-01', '2027-06-15'],
            6 => ['2027-06-16', '2027-07-31'],
        ];

        foreach (range(1, 6) as $position) {
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
