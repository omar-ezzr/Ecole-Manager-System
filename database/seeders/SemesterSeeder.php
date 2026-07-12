<?php

namespace Database\Seeders;

use App\Models\Semester;
use Illuminate\Database\Seeder;

class SemesterSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 6) as $position) {
            Semester::updateOrCreate(
                ['position' => $position],
                [
                    'name' => 'Semester '.$position,
                    'code' => 'semester_'.$position,
                    'position' => $position,
                ]
            );
        }
    }
}
