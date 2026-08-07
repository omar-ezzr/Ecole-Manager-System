<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\StudentGrade;
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
        return $user->can(P::GRADES_ALL)
            || (($user->can(P::GRADES_ASSIGNED) || $user->can(P::GRADES_OWN))
                && Student::visibleTo($user)->whereKey($studentGrade->student_id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->can(P::GRADES_MANAGE_ALL);
    }

    public function createForStudent(User $user, Student $student): bool
    {
        return $user->can(P::GRADES_MANAGE_ALL)
            || ($user->can(P::GRADES_MANAGE_ASSIGNED)
                && Student::visibleTo($user)->whereKey($student->id)->exists());
    }

    public function update(User $user, StudentGrade $studentGrade): bool
    {
        return $user->can(P::GRADES_MANAGE_ALL)
            || ($user->can(P::GRADES_MANAGE_ASSIGNED)
                && Student::visibleTo($user)->whereKey($studentGrade->student_id)->exists());
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
}
