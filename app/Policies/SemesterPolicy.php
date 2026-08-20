<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use App\Support\SchoolPermissions as P;

class SemesterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(P::SEMESTERS_VIEW);
    }

    public function view(User $user, Semester $semester): bool
    {
        if (! $user->can(P::SEMESTERS_VIEW)) {
            return false;
        }

        if ($user->hasAnyRole([Role::ROLE_ADMINISTRATOR, Role::ROLE_DIRECTOR, Role::ROLE_SECRETARY])) {
            return true;
        }

        if ($user->isProfessor()) {
            return $user->teachingAssignments()->where('academic_year_id', $semester->academic_year_id)->exists();
        }

        return $user->student_id !== null
            && $user->student?->grades()->where('semester_id', $semester->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can(P::SEMESTERS_MANAGE);
    }

    public function update(User $user, Semester $semester): bool
    {
        return $user->can(P::SEMESTERS_MANAGE);
    }

    public function delete(User $user, Semester $semester): bool
    {
        return $user->can(P::SEMESTERS_MANAGE);
    }
}
