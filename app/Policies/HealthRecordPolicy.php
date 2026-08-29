<?php

namespace App\Policies;

use App\Models\HealthRecord;
use App\Models\Student;
use App\Models\User;
use App\Support\SchoolPermissions as P;

class HealthRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(P::HEALTH_VIEW);
    }

    public function view(User $user, HealthRecord $healthRecord): bool
    {
        return $user->can(P::HEALTH_VIEW)
            && Student::visibleTo($user)->whereKey($healthRecord->student_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can(P::HEALTH_MANAGE);
    }

    public function update(User $user, HealthRecord $healthRecord): bool
    {
        return $user->can(P::HEALTH_MANAGE) && Student::visibleTo($user)->whereKey($healthRecord->student_id)->exists();
    }

    public function delete(User $user, HealthRecord $healthRecord): bool
    {
        return $user->can(P::HEALTH_MANAGE) && Student::visibleTo($user)->whereKey($healthRecord->student_id)->exists();
    }

    public function restore(User $user, HealthRecord $healthRecord): bool
    {
        return $this->delete($user, $healthRecord);
    }

    public function forceDelete(User $user, HealthRecord $healthRecord): bool
    {
        return $this->delete($user, $healthRecord);
    }
}
