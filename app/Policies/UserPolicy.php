<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->hasPermission('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->hasPermission('users.view');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->hasPermission('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->hasPermission('users.update');
    }

    public function deactivate(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->hasPermission('users.deactivate') && $user->id !== $model->id;
    }
}
