<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use App\Support\SchoolPermissions as P;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([P::STUDENTS_ALL, P::STUDENTS_ASSIGNED, P::STUDENTS_OWN]);
    }

    public function view(User $user, Student $student): bool
    {
        return Student::visibleTo($user)->whereKey($student->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $user->can(P::STUDENTS_CREATE);
    }

    public function update(User $user, Student $student): bool
    {
        return $user->can(P::STUDENTS_UPDATE) && Student::visibleTo($user)->whereKey($student->getKey())->exists();
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->can(P::STUDENTS_DELETE) && Student::visibleTo($user)->whereKey($student->getKey())->exists();
    }

    public function restore(User $user, Student $student): bool
    {
        return $user->can(P::STUDENTS_DELETE);
    }

    public function forceDelete(User $user, Student $student): bool
    {
        return $user->can(P::STUDENTS_DELETE);
    }
}
