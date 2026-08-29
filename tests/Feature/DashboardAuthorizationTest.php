<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_output_does_not_expose_removed_domains(): void
    {
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($director)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Compagnie')
            ->assertDontSee('Groupement')
            ->assertDontSee('CIE')
            ->assertDontSee('GPT')
            ->assertDontSee('compagnie_id')
            ->assertDontSee('groupement_id');
    }

    public function test_director_dashboard_is_read_only(): void
    {
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($director)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('View students')
            ->assertDontSee('Add student')
            ->assertDontSee('Create manually')
            ->assertDontSee('Create record');
    }

    public function test_professor_dashboard_does_not_expose_unassigned_classrooms(): void
    {
        [$assignedClassroom, $otherClassroom] = Classroom::orderBy('id')->limit(2)->get()->all();
        $assignedClassroom->update(['name' => 'Professor Dashboard Classroom']);
        $otherClassroom->update(['name' => 'Restricted Dashboard Classroom']);
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        TeachingAssignment::factory()->create([
            'professor_id' => $professor->id,
            'classroom_id' => $assignedClassroom->id,
            'subject_id' => Subject::factory()->create(['code' => 'DASH-SCOPE'])->id,
            'academic_year_id' => AcademicYear::active()->firstOrFail()->id,
        ]);
        Student::factory()->create(['classroom_id' => $assignedClassroom->id]);
        Student::factory()->create(['classroom_id' => $otherClassroom->id]);

        $this->actingAs($professor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($assignedClassroom->name)
            ->assertDontSee($otherClassroom->name);
    }

    public function test_professor_dashboard_uses_weighted_grades_from_active_year_only(): void
    {
        $academicYear = AcademicYear::active()->firstOrFail();
        $semester = Semester::where('academic_year_id', $academicYear->id)->where('sequence', 1)->firstOrFail();
        $classroom = Classroom::firstOrFail();
        $classroom->update(['name' => 'Weighted Dashboard Classroom']);
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);

        foreach ([
            ['code' => 'DASH-W1', 'name' => 'Dashboard Weighted One', 'grade' => 10, 'coefficient' => 1],
            ['code' => 'DASH-W2', 'name' => 'Dashboard Weighted Two', 'grade' => 20, 'coefficient' => 3],
        ] as $row) {
            $subject = Subject::create([
                'code' => $row['code'],
                'name' => $row['name'],
                'is_active' => true,
                'semester_id' => null,
            ]);
            $assignment = TeachingAssignment::create([
                'professor_id' => $professor->id,
                'classroom_id' => $classroom->id,
                'subject_id' => $subject->id,
                'academic_year_id' => $academicYear->id,
            ]);
            StudentGrade::factory()->forAssignment($assignment, $student, $semester)->create([
                'grade' => $row['grade'],
                'coefficient' => $row['coefficient'],
            ]);
        }

        $otherYear = AcademicYear::create([
            'name' => '2032-2033',
            'starts_at' => '2032-09-01',
            'ends_at' => '2033-07-31',
            'is_active' => false,
        ]);
        $otherSemester = Semester::create([
            'academic_year_id' => $otherYear->id,
            'name' => 'Semester 1',
            'code' => 'semester_1_2032',
            'position' => 1,
            'sequence' => 1,
            'starts_at' => '2032-09-01',
            'ends_at' => '2032-12-31',
        ]);
        $otherSubject = Subject::create([
            'code' => 'DASH-OTHER-YEAR',
            'name' => 'Other Year Dashboard Subject',
            'is_active' => true,
            'semester_id' => null,
        ]);
        $otherAssignment = TeachingAssignment::create([
            'professor_id' => $professor->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $otherSubject->id,
            'academic_year_id' => $otherYear->id,
        ]);
        StudentGrade::factory()->forAssignment($otherAssignment, $student, $otherSemester)->create([
            'grade' => 0,
            'coefficient' => 100,
        ]);

        $this->actingAs($professor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('semesterAverageCharts', function (array $charts) use ($classroom): bool {
                $chart = $charts[1];
                $index = array_search($classroom->name, $chart['labels'], true);

                return $index !== false && $chart['data'][$index] === 17.5;
            });
    }
}
