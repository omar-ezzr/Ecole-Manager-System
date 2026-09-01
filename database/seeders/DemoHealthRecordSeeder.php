<?php

namespace Database\Seeders;

use App\Models\HealthRecord;
use App\Models\Student;
use Illuminate\Database\Seeder;

class DemoHealthRecordSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['STU-0001', '2026-09-15', 'Ophtalmologie', 'Contrôle visuel de routine. Aucun traitement nécessaire.'],
            ['STU-0003', '2026-10-03', 'Fièvre', 'Repos de deux jours et bonne hydratation.'],
            ['STU-0005', '2026-10-21', 'Odontologie', 'Contrôle dentaire recommandé.'],
            ['STU-0008', '2026-11-07', 'Asthénie', 'Suivi de la fatigue et conseils d’hygiène de vie.'],
            ['STU-0012', '2026-11-19', 'Dermatologie', 'Crème locale prescrite pendant une semaine.'],
            ['STU-0017', '2026-12-04', 'Podologie', 'Limitation temporaire des activités sportives.'],
        ];

        foreach ($records as [$studentNumber, $date, $type, $prescription]) {
            $student = Student::where('student_number', $studentNumber)->first();

            if (! $student) {
                continue;
            }

            HealthRecord::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'date' => $date,
                    'type' => $type,
                ],
                ['medical_prescription' => $prescription]
            );
        }
    }
}
