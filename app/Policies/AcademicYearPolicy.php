<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\User;
use App\Support\SchoolPermissions as P;

class AcademicYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(P::ACADEMIC_YEARS_VIEW);
    }

    public function view(User $user, AcademicYear $academicYear): bool
    {
        if (! $user->can(P::ACADEMIC_YEARS_VIEW)) {
            return false;
        }

        if ($user->hasAnyRole([Role::ROLE_ADMINISTRATOR, Role::ROLE_DIRECTOR, Role::ROLE_SECRETARY])) {
            return true;
        }

        if ($user->isProfessor()) {
            return $user->teachingAssignments()->where('academic_year_id', $academicYear->id)->exists();
        }

        return $user->student_id !== null
            && $user->student?->grades()
                ->whereHas('semester', fn ($query) => $query->where('academic_year_id', $academicYear->id))
                ->exists();
    }

    public function create(User $user): bool
    {
        return $user->can(P::ACADEMIC_YEARS_MANAGE);
    }

    public function update(User $user, AcademicYear $academicYear): bool
    {
        return $user->can(P::ACADEMIC_YEARS_MANAGE);
    }

    public function delete(User $user, AcademicYear $academicYear): bool
    {
        return $user->can(P::ACADEMIC_YEARS_MANAGE);
    }
}
