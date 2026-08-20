<?php

namespace App\Policies;

use App\Models\TeachingAssignment;
use App\Models\User;
use App\Support\SchoolPermissions as P;

class TeachingAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([P::TEACHING_ASSIGNMENTS_VIEW_ALL, P::TEACHING_ASSIGNMENTS_VIEW_OWN]);
    }

    public function view(User $user, TeachingAssignment $teachingAssignment): bool
    {
        return $user->can(P::TEACHING_ASSIGNMENTS_VIEW_ALL)
            || ($user->can(P::TEACHING_ASSIGNMENTS_VIEW_OWN)
                && $teachingAssignment->professor_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->can(P::TEACHING_ASSIGNMENTS_MANAGE);
    }

    public function update(User $user, TeachingAssignment $teachingAssignment): bool
    {
        return $user->can(P::TEACHING_ASSIGNMENTS_MANAGE);
    }

    public function delete(User $user, TeachingAssignment $teachingAssignment): bool
    {
        return $user->can(P::TEACHING_ASSIGNMENTS_MANAGE);
    }
}
