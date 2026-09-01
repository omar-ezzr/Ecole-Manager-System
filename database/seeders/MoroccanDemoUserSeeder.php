<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomSubject;
use App\Models\Role;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class MoroccanDemoUserSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'ecole123';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Moroccan demo accounts are only seeded outside production.');

            return;
        }

        $this->seedStaffAccounts();
        $this->seedStudentAccounts();
        $this->seedTeachingAssignments();
    }

    private function seedStaffAccounts(): void
    {
        foreach ([
            ['Nadia El Mansouri', 'director@ecole.local', Role::ROLE_DIRECTOR],
            ['Salma Benjelloun', 'secretariat@ecole.local', Role::ROLE_SECRETARY],
            ['Youssef Alaoui', 'professeur1@ecole.local', Role::ROLE_PROFESSOR],
            ['Imane Berrada', 'professeur2@ecole.local', Role::ROLE_PROFESSOR],
            ['Khalid El Amrani', 'professeur3@ecole.local', Role::ROLE_PROFESSOR],
        ] as [$name, $email, $role]) {
            $this->syncUser($name, $email, $role);
        }
    }

    private function seedStudentAccounts(): void
    {
        $students = Student::query()
            ->where('student_number', 'like', 'STU-%')
            ->orderBy('student_number')
            ->limit(10)
            ->get();

        if ($students->count() !== 10) {
            throw new RuntimeException('Moroccan demo student accounts require STU-0001 through STU-0010.');
        }

        $students->each(fn (Student $student) => $this->syncUser(
            "{$student->first_name} {$student->last_name}",
            $student->email,
            Role::ROLE_STUDENT,
            $student->id
        ));
    }

    private function seedTeachingAssignments(): void
    {
        $academicYear = AcademicYear::active()->firstOrFail();
        $users = User::query()
            ->whereIn('email', ['professeur1@ecole.local', 'professeur2@ecole.local', 'professeur3@ecole.local'])
            ->pluck('id', 'email');
        $classrooms = Classroom::query()
            ->whereIn('name', ['TC-1', 'TC-2', '1BAC-SC-1', '1BAC-SC-2', '2BAC-PC-1', '2BAC-SVT-1'])
            ->pluck('id', 'name');
        $subjects = Subject::query()
            ->whereIn('code', ['MAT', 'FRA', 'ENG', 'PHY', 'INF', 'SVT'])
            ->pluck('id', 'code');

        if ($users->count() !== 3 || $classrooms->count() !== 6 || $subjects->count() !== 6) {
            throw new RuntimeException('Moroccan demo teaching assignments require the demo users, classrooms, and subjects.');
        }

        $assignments = [
            ['professeur1@ecole.local', 'TC-1', 'MAT'], ['professeur1@ecole.local', 'TC-2', 'MAT'],
            ['professeur1@ecole.local', '1BAC-SC-1', 'MAT'], ['professeur1@ecole.local', '1BAC-SC-2', 'MAT'],
            ['professeur1@ecole.local', '2BAC-PC-1', 'MAT'], ['professeur1@ecole.local', '2BAC-SVT-1', 'MAT'],
            ['professeur2@ecole.local', 'TC-1', 'FRA'], ['professeur2@ecole.local', 'TC-2', 'ENG'],
            ['professeur2@ecole.local', '1BAC-SC-1', 'FRA'], ['professeur2@ecole.local', '1BAC-SC-2', 'ENG'],
            ['professeur2@ecole.local', '2BAC-PC-1', 'ENG'], ['professeur2@ecole.local', '2BAC-SVT-1', 'FRA'],
            ['professeur3@ecole.local', 'TC-1', 'INF'], ['professeur3@ecole.local', 'TC-2', 'INF'],
            ['professeur3@ecole.local', '1BAC-SC-1', 'PHY'], ['professeur3@ecole.local', '1BAC-SC-2', 'PHY'],
            ['professeur3@ecole.local', '2BAC-PC-1', 'PHY'], ['professeur3@ecole.local', '2BAC-SVT-1', 'SVT'],
        ];

        $validAssignments = collect($assignments)->map(fn (array $assignment) => implode(':', [
            $users[$assignment[0]], $classrooms[$assignment[1]], $subjects[$assignment[2]],
        ]))->all();

        TeachingAssignment::query()->where('academic_year_id', $academicYear->id)
            ->whereIn('professor_id', $users->values())->whereIn('classroom_id', $classrooms->values())->get()
            ->reject(fn (TeachingAssignment $assignment) => in_array(
                implode(':', [$assignment->professor_id, $assignment->classroom_id, $assignment->subject_id]), $validAssignments, true
            ))->each->delete();

        $validClassroomSubjects = collect($assignments)->map(fn (array $assignment) => implode(':', [
            $classrooms[$assignment[1]], $subjects[$assignment[2]],
        ]))->all();

        ClassroomSubject::query()->where('academic_year_id', $academicYear->id)
            ->whereIn('classroom_id', $classrooms->values())->get()
            ->reject(fn (ClassroomSubject $assignment) => in_array(
                implode(':', [$assignment->classroom_id, $assignment->subject_id]), $validClassroomSubjects, true
            ))->each->delete();

        foreach ($assignments as [$email, $classroomName, $subjectCode]) {
            $classroomId = $classrooms[$classroomName];
            $subjectId = $subjects[$subjectCode];

            ClassroomSubject::updateOrCreate(
                [
                    'classroom_id' => $classroomId,
                    'subject_id' => $subjectId,
                    'academic_year_id' => $academicYear->id,
                ]
            );

            TeachingAssignment::updateOrCreate(
                [
                    'professor_id' => $users[$email],
                    'classroom_id' => $classroomId,
                    'subject_id' => $subjectId,
                    'academic_year_id' => $academicYear->id,
                ]
            );
        }
    }

    private function syncUser(string $name, string $email, string $role, ?int $studentId = null): void
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->user_type = $role;
        $user->student_id = $studentId;
        $user->is_active = true;
        $user->email_verified_at = now();

        if (! $user->exists || ! Hash::check(self::DEMO_PASSWORD, (string) $user->password)) {
            $user->password = Hash::make(self::DEMO_PASSWORD);
        }

        $user->save();
        $user->syncRoles([$role]);
    }
}
