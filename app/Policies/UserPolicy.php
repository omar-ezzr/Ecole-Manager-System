<?php

namespace App\Policies;

use App\Models\User;
use App\Support\SchoolPermissions as P;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->administratorCan($user, P::USERS_VIEW);
    }

    public function view(User $user, User $model): bool
    {
        return $this->administratorCan($user, P::USERS_VIEW);
    }

    public function create(User $user): bool
    {
        return $this->administratorCan($user, P::USERS_CREATE);
    }

    public function update(User $user, User $model): bool
    {
        return $this->administratorCan($user, P::USERS_UPDATE);
    }

    public function delete(User $user, User $model): bool
    {
        return ! $user->is($model)
            && $this->administratorCan($user, P::USERS_DELETE);
    }

    public function restore(User $user, User $model): bool
    {
        return $this->administratorCan($user, P::USERS_DELETE);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $this->administratorCan($user, P::USERS_DELETE);
    }

    private function administratorCan(User $user, string $permission): bool
    {
        return $user->isOperationalAdministrator()
            && $user->can($permission);
    }
}
