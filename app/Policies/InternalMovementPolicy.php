<?php

namespace App\Policies;

use App\Enums\InternalMovementStatus;
use App\Models\InternalMovement;
use App\Models\User;

class InternalMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('internal_movements.view');
    }

    public function view(User $user, InternalMovement $internalMovement): bool
    {
        return $user->hasPermission('internal_movements.view')
            && ($user->isAdmin() || $user->canAccessLocation($internalMovement->location_id));
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('internal_movements.create') && $user->isShopUser();
    }

    public function approve(User $user, InternalMovement $internalMovement): bool
    {
        return $user->hasPermission('internal_movements.approve')
            && ($user->isAdmin() || $user->hasRole('shop_manager'))
            && $user->canAccessLocation($internalMovement->location_id)
            && $internalMovement->status === InternalMovementStatus::Escalated;
    }

    public function reverse(User $user, InternalMovement $internalMovement): bool
    {
        return $user->hasPermission('internal_movements.reverse')
            && ($user->isAdmin() || $user->hasRole('shop_manager'))
            && $user->canAccessLocation($internalMovement->location_id)
            && $internalMovement->status === InternalMovementStatus::Completed;
    }
}
