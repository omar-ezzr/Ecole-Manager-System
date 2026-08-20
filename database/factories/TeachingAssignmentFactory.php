<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeachingAssignmentFactory extends Factory
{
    protected $model = TeachingAssignment::class;

    public function definition(): array
    {
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $academicYearId = AcademicYear::query()->value('id') ?? AcademicYear::factory()->create()->id;

        return [
            'professor_id' => $professor->id,
            'classroom_id' => Classroom::query()->inRandomOrder()->value('id'),
            'subject_id' => Subject::factory()->create()->id,
            'academic_year_id' => $academicYearId,
        ];
    }
}
