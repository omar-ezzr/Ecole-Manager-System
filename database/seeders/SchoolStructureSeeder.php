<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Department;
use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolStructureSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::updateOrCreate(
            ['name' => 'Ecole Manager Demo School'],
            [
                'country' => 'Morocco',
                'region' => 'Casablanca-Settat',
                'city' => 'Casablanca',
                'address' => '12 Avenue des Ecoles',
            ]
        );

        $departments = [
            'Computer Science' => ['address' => 'Building A'],
            'Business Management' => ['address' => 'Building B'],
            'General Studies' => ['address' => 'Building C'],
        ];

        foreach ($departments as $name => $attributes) {
            $department = Department::updateOrCreate(
                ['name' => $name],
                [
                    'school_id' => $school->id,
                    'address' => $attributes['address'],
                ]
            );

            foreach ($this->classroomsForDepartment($name) as $classroomName) {
                Classroom::updateOrCreate(
                    ['name' => $classroomName],
                    [
                        'department_id' => $department->id,
                        'address' => $attributes['address'].' - Room '.$classroomName,
                    ]
                );
            }
        }
    }

    private function classroomsForDepartment(string $department): array
    {
        return match ($department) {
            'Computer Science' => ['CS-101', 'CS-102'],
            'Business Management' => ['BM-101', 'BM-102'],
            default => ['GS-101', 'GS-102'],
        };
    }
}
