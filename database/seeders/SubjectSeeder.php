<?php

namespace Database\Seeders;

use App\Models\Semester;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            1 => ['Mathematics', 'Introduction to Programming', 'Communication'],
            2 => ['Databases', 'Web Development', 'Algorithms'],
            3 => ['Software Engineering', 'Networks', 'Operating Systems'],
            4 => ['Laravel', 'Project Management', 'Security Basics'],
            5 => ['Internship Preparation', 'Advanced Web Development'],
            6 => ['Final Project', 'Professional Integration'],
        ];

        foreach ($subjects as $position => $names) {
            $semester = Semester::where('position', $position)->first();

            if (! $semester) {
                continue;
            }

            foreach ($names as $name) {
                Subject::updateOrCreate(
                    [
                        'semester_id' => $semester->id,
                        'code' => $semester->code.'_'.Str::slug($name, '_'),
                    ],
                    [
                        'semester_id' => $semester->id,
                        'name' => $name,
                    ]
                );
            }
        }
    }
}
