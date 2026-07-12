<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;
use App\Support\SchoolPermissions as P;

class SchoolPolicy
{
    public function viewAny(User $user): bool { return $user->can(P::SCHOOLS_VIEW); }
    public function view(User $user, School $school): bool { return $user->can(P::SCHOOLS_VIEW); }
    public function create(User $user): bool { return $user->can(P::SCHOOLS_MANAGE); }
    public function update(User $user, School $school): bool { return $user->can(P::SCHOOLS_MANAGE); }
    public function delete(User $user, School $school): bool { return $user->can(P::SCHOOLS_MANAGE); }
}
