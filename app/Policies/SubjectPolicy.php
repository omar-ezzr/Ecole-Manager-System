<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use App\Support\SchoolPermissions as P;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(P::SUBJECTS_VIEW);
    }

    public function view(User $user, Subject $subject): bool
    {
        if (! $user->can(P::SUBJECTS_VIEW)) {
            return false;
        }

        if ($user->hasAnyRole([Role::ROLE_ADMINISTRATOR, Role::ROLE_DIRECTOR, Role::ROLE_SECRETARY])) {
            return true;
        }

        if ($user->isProfessor()) {
            return $user->teachingAssignments()->where('subject_id', $subject->id)->exists();
        }

        return $user->student_id !== null
            && $user->student?->grades()->where('subject_id', $subject->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can(P::SUBJECTS_MANAGE);
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->can(P::SUBJECTS_MANAGE);
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->can(P::SUBJECTS_MANAGE);
    }
}
