<?php

namespace App\Policies;

use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Support\SchoolPermissions as P;

class StudentGradePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([P::GRADES_ALL, P::GRADES_ASSIGNED, P::GRADES_OWN]);
    }

    public function view(User $user, StudentGrade $studentGrade): bool
    {
        if ($user->can(P::GRADES_ALL)) {
            return true;
        }

        if ($studentGrade->teaching_assignment_id !== null) {
            if ($user->can(P::GRADES_OWN)) {
                return $user->student_id === $studentGrade->student_id;
            }

            return $user->can(P::GRADES_ASSIGNED)
                && $this->belongsToProfessorAssignment($user, $studentGrade);
        }

        return $user->can(P::GRADES_ALL)
            || ($user->can(P::GRADES_OWN)
                && $user->student_id === $studentGrade->student_id);
    }

    public function create(User $user): bool
    {
        return $user->can(P::GRADES_MANAGE_ALL);
    }

    public function createForStudent(User $user, Student $student): bool
    {
        return $user->can(P::GRADES_MANAGE_ALL);
    }

    public function createForAssignment(
        User $user,
        TeachingAssignment $teachingAssignment,
        Student $student,
        Semester $semester
    ): bool {
        if ($user->can(P::GRADES_MANAGE_ALL)) {
            return true;
        }

        if (! $user->can(P::GRADES_MANAGE_ASSIGNED) || ! $user->isProfessor()) {
            return false;
        }

        return $teachingAssignment->professor_id === $user->id
            && $teachingAssignment->classroom_id === $student->classroom_id
            && $semester->academic_year_id === $teachingAssignment->academic_year_id
            && $teachingAssignment->subject?->is_active === true;
    }

    public function update(User $user, StudentGrade $studentGrade): bool
    {
        if ($user->can(P::GRADES_MANAGE_ALL)) {
            return true;
        }

        if ($studentGrade->teaching_assignment_id !== null) {
            return $user->can(P::GRADES_MANAGE_ASSIGNED)
                && $user->isProfessor()
                && $this->belongsToProfessorAssignment($user, $studentGrade);
        }

        return false;
    }

    public function delete(User $user, StudentGrade $studentGrade): bool
    {
        return $this->update($user, $studentGrade);
    }

    public function restore(User $user, StudentGrade $studentGrade): bool
    {
        return $this->delete($user, $studentGrade);
    }

    public function forceDelete(User $user, StudentGrade $studentGrade): bool
    {
        return $this->delete($user, $studentGrade);
    }

    private function belongsToProfessorAssignment(User $user, StudentGrade $studentGrade): bool
    {
        $assignment = $studentGrade->teachingAssignment;

        return $assignment?->professor_id === $user->id
            && $assignment->classroom_id === $studentGrade->student?->classroom_id
            && $assignment->academic_year_id === $studentGrade->semester?->academic_year_id
            && $assignment->subject_id === $studentGrade->subject_id;
    }
}
