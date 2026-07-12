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
            ['STD-0001', '2025-01-15', 'Ophtalmologie', 'Routine eye check. No medication required.'],
            ['STD-0003', '2025-02-03', 'Fievre', 'Rest for two days and hydration.'],
            ['STD-0005', '2025-02-21', 'Odontologie', 'Dental follow-up recommended.'],
            ['STD-0008', '2025-03-07', 'Asthenie', 'Vitamin supplement and monitoring.'],
            ['STD-0012', '2025-03-19', 'Dermatologie et Affections', 'Topical cream for seven days.'],
            ['STD-0017', '2025-04-04', 'Podologie', 'Sports activity limitation for one week.'],
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
                [
                    'medical_prescription' => $prescription,
                ]
            );
        }
    }
}
