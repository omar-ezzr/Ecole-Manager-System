<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentGradeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_creation_is_authorized_against_target_student(): void
    {
        [$assignedClassroom, $otherClassroom] = Classroom::orderBy('id')->limit(2)->get()->all();
        $assignedStudent = Student::factory()->create(['classroom_id' => $assignedClassroom->id]);
        $otherStudent = Student::factory()->create(['classroom_id' => $otherClassroom->id]);

        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);
        $studentUser = User::factory()->create(['student_id' => $assignedStudent->id]);
        $studentUser->assignRole(Role::ROLE_STUDENT);
        $unassignedProfessor = User::factory()->create();
        $unassignedProfessor->assignRole(Role::ROLE_PROFESSOR);
        $professor = User::factory()->create();
        $professor->assignRole(Role::ROLE_PROFESSOR);
        $professor->assignedClassrooms()->sync([$assignedClassroom->id]);

        $this->assertTrue($admin->can('createForStudent', [StudentGrade::class, $assignedStudent]));
        $this->assertTrue($professor->can('createForStudent', [StudentGrade::class, $assignedStudent]));
        $this->assertFalse($professor->can('createForStudent', [StudentGrade::class, $otherStudent]));
        $this->assertFalse($unassignedProfessor->can('createForStudent', [StudentGrade::class, $assignedStudent]));
        $this->assertFalse($director->can('createForStudent', [StudentGrade::class, $assignedStudent]));
        $this->assertFalse($studentUser->can('createForStudent', [StudentGrade::class, $assignedStudent]));
        $this->assertFalse(User::factory()->create()->can('createForStudent', [StudentGrade::class, $assignedStudent]));
    }
}
