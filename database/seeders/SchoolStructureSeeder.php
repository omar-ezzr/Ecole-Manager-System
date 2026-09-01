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
            ['name' => 'Ecole Al Khawarizmi'],
            [
                'country' => 'Morocco',
                'region' => 'Rabat-Salé-Kénitra',
                'city' => 'Salé',
                'address' => 'Avenue Ibn Sina, Salé, Maroc',
            ]
        );

        $departments = [
            'Tronc Commun' => [
                'address' => 'Bâtiment A',
                'classrooms' => ['TC-1', 'TC-2'],
            ],
            'Sciences Expérimentales' => [
                'address' => 'Bâtiment B',
                'classrooms' => ['1BAC-SC-1', '1BAC-SC-2'],
            ],
            'Baccalauréat Sciences' => [
                'address' => 'Bâtiment C',
                'classrooms' => ['2BAC-PC-1', '2BAC-SVT-1'],
            ],
        ];

        foreach ($departments as $name => $attributes) {
            $department = Department::updateOrCreate(
                ['school_id' => $school->id, 'name' => $name],
                ['address' => $attributes['address']]
            );

            foreach ($attributes['classrooms'] as $classroomName) {
                Classroom::updateOrCreate(
                    ['department_id' => $department->id, 'name' => $classroomName],
                    [
                        'address' => $attributes['address'].' - Salle '.$classroomName,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
