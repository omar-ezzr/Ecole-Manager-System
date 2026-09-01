<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['code' => 'MAT', 'name' => 'Mathématiques', 'description' => 'Algèbre, analyse et raisonnement mathématique.'],
            ['code' => 'PHY', 'name' => 'Physique-Chimie', 'description' => 'Sciences physiques et chimie expérimentale.'],
            ['code' => 'FRA', 'name' => 'Français', 'description' => 'Langue française, expression et littérature.'],
            ['code' => 'ARA', 'name' => 'Langue Arabe', 'description' => 'Langue arabe, expression et littérature.'],
            ['code' => 'ENG', 'name' => 'Anglais', 'description' => 'Communication et compréhension en anglais.'],
            ['code' => 'INF', 'name' => 'Informatique', 'description' => 'Algorithmique, programmation et usages numériques.'],
            ['code' => 'SVT', 'name' => 'Sciences de la Vie et de la Terre', 'description' => 'Sciences du vivant, géologie et environnement.'],
            ['code' => 'HGE', 'name' => 'Histoire-Géographie', 'description' => 'Repères historiques, géographiques et civiques.'],
            ['code' => 'EIS', 'name' => 'Éducation Islamique', 'description' => 'Valeurs, culture et éducation islamique.'],
            ['code' => 'PHI', 'name' => 'Philosophie', 'description' => 'Pensée critique, argumentation et culture philosophique.'],
        ];

        foreach ($subjects as $subject) {
            Subject::updateOrCreate(
                ['code' => $subject['code']],
                [
                    'name' => $subject['name'],
                    'description' => $subject['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
