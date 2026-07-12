<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;
use App\Support\SchoolPermissions as P;

class ClassroomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([P::CLASSROOMS_ALL, P::CLASSROOMS_ASSIGNED]);
    }

    public function view(User $user, Classroom $classroom): bool
    {
        return $user->can(P::CLASSROOMS_ALL)
            || ($user->can(P::CLASSROOMS_ASSIGNED)
                && $user->assignedClassrooms()->whereKey($classroom->id)->exists());
    }

    public function create(User $user): bool { return $user->can(P::CLASSROOMS_MANAGE); }
    public function update(User $user, Classroom $classroom): bool { return $user->can(P::CLASSROOMS_MANAGE); }
    public function delete(User $user, Classroom $classroom): bool { return $user->can(P::CLASSROOMS_MANAGE); }
}
