<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        $activeYear = AcademicYear::updateOrCreate(
            ['name' => '2026-2027'],
            [
                'starts_at' => '2026-09-01',
                'ends_at' => '2027-07-31',
                'is_active' => true,
            ]
        );

        AcademicYear::whereKeyNot($activeYear->id)->update(['is_active' => false]);
    }
}
