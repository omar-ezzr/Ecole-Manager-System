<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MoroccanDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_moroccan_demo_seeders_are_complete_and_idempotent(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $adminId = $admin->id;
        $adminPassword = $admin->getRawOriginal('password');
        $adminRoles = $admin->getRoleNames()->sort()->values()->all();

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $students = Student::query()
            ->where('student_number', 'like', 'STU-%')
            ->orderBy('student_number')
            ->get();
        $academicYear = AcademicYear::active()->firstOrFail();

        $this->assertCount(100, $students);
        $this->assertSame(100, $students->pluck('student_number')->unique()->count());
        $this->assertSame('STU-0001', $students->first()->student_number);
        $this->assertSame('STU-0100', $students->last()->student_number);
        $this->assertSame(0, $students->whereNull('classroom_id')->count());
        $this->assertSame(
            100,
            StudentEnrollment::query()
                ->where('academic_year_id', $academicYear->id)
                ->whereNull('left_at')
                ->whereIn('student_id', $students->modelKeys())
                ->count()
        );
        $this->assertSame(
            0,
            StudentEnrollment::query()
                ->whereIn('student_id', $students->modelKeys())
                ->whereNull('left_at')
                ->selectRaw('student_id, count(*) as enrollment_count')
                ->groupBy('student_id')
                ->having('enrollment_count', '>', 1)
                ->count()
        );
        $this->assertSame(
            200,
            StudentGrade::query()
                ->whereIn('student_id', $students->modelKeys())
                ->whereNull('teaching_assignment_id')
                ->whereNull('subject_id')
                ->count()
        );

        $this->assertSame(
            ['ARA', 'EIS', 'ENG', 'FRA', 'HGE', 'INF', 'MAT', 'PHI', 'PHY', 'SVT'],
            Subject::query()
                ->whereIn('code', ['MAT', 'PHY', 'FRA', 'ARA', 'ENG', 'INF', 'SVT', 'HGE', 'EIS', 'PHI'])
                ->orderBy('code')
                ->pluck('code')
                ->all()
        );
        $this->assertSame(
            [1, 2],
            Semester::query()
                ->where('academic_year_id', $academicYear->id)
                ->orderBy('sequence')
                ->pluck('sequence')
                ->all()
        );

        $accounts = [
            'director@ecole.local' => Role::ROLE_DIRECTOR,
            'secretariat@ecole.local' => Role::ROLE_SECRETARY,
            'professeur1@ecole.local' => Role::ROLE_PROFESSOR,
            'professeur2@ecole.local' => Role::ROLE_PROFESSOR,
            'professeur3@ecole.local' => Role::ROLE_PROFESSOR,
        ];

        foreach ($students->take(10) as $student) {
            $accounts[$student->email] = Role::ROLE_STUDENT;
        }

        foreach ($accounts as $email => $role) {
            $user = User::where('email', $email)->firstOrFail();

            $this->assertTrue($user->is_active);
            $this->assertNotNull($user->email_verified_at);
            $this->assertTrue($user->hasRole($role));
            $this->assertTrue(Hash::check('ecole123', $user->password));
        }

        $this->assertSame(10, User::query()
            ->whereIn('student_id', $students->modelKeys())
            ->whereHas('roles', fn ($roles) => $roles->where('name', Role::ROLE_STUDENT))
            ->count());
        $this->assertSame(90, $students->filter(fn (Student $student) => $student->user()->doesntExist())->count());

        $professors = User::query()
            ->whereIn('email', ['professeur1@ecole.local', 'professeur2@ecole.local', 'professeur3@ecole.local'])
            ->get();
        $this->assertSame(6, TeachingAssignment::query()
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('professor_id', $professors->modelKeys())
            ->count());
        foreach ($professors as $professor) {
            $this->assertSame(2, TeachingAssignment::query()
                ->where('academic_year_id', $academicYear->id)
                ->where('professor_id', $professor->id)
                ->count());
        }

        $admin->refresh();

        $this->assertSame($adminId, $admin->id);
        $this->assertSame($adminPassword, $admin->getRawOriginal('password'));
        $this->assertSame($adminRoles, $admin->getRoleNames()->sort()->values()->all());
    }
}
