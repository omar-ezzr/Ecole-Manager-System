<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['MAT', 'Mathematics'],
            ['PHY', 'Physics'],
            ['ENG', 'English'],
            ['BIO', 'Biology'],
            ['CHE', 'Chemistry'],
            ['INF', 'Informatics'],
            ['DBS', 'Databases'],
            ['WEB', 'Web Development'],
            ['ALG', 'Algorithms'],
        ];

        foreach ($subjects as [$code, $name]) {
            Subject::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => 'Core subject for the academic program.',
                    'is_active' => true,
                    'semester_id' => null,
                ]
            );
        }
    }
}
