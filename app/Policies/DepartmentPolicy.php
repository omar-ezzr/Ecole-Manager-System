<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use App\Support\SchoolPermissions as P;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(P::DEPARTMENTS_VIEW);
    }

    public function view(User $user, Department $department): bool
    {
        return $user->can(P::DEPARTMENTS_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(P::DEPARTMENTS_MANAGE);
    }

    public function update(User $user, Department $department): bool
    {
        return $user->can(P::DEPARTMENTS_MANAGE);
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->can(P::DEPARTMENTS_MANAGE);
    }

    public function restore(User $user, Department $department): bool
    {
        return $user->can(P::DEPARTMENTS_MANAGE);
    }

    public function forceDelete(User $user, Department $department): bool
    {
        return $user->can(P::DEPARTMENTS_MANAGE);
    }
}
