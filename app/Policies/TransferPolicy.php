<?php

namespace App\Policies;

use App\Enums\LocationType;
use App\Enums\TransferStatus;
use App\Models\Transfer;
use App\Models\User;

class TransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('transfers.view');
    }

    public function view(User $user, Transfer $transfer): bool
    {
        if (! $user->hasPermission('transfers.view')) {
            return false;
        }

        return $user->isAdmin()
            || $user->canAccessLocation($transfer->source_location_id)
            || $user->canAccessLocation($transfer->destination_location_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('transfers.create') && ($user->isAdmin() || $user->isWarehouseUser());
    }

    public function approve(User $user, Transfer $transfer): bool
    {
        return $user->hasPermission('transfers.approve')
            && $user->isAdmin()
            && $transfer->status === TransferStatus::PendingApproval;
    }

    public function dispatch(User $user, Transfer $transfer): bool
    {
        return $user->hasPermission('transfers.dispatch')
            && ($user->isAdmin() || $user->isWarehouseUser())
            && $user->canAccessLocation($transfer->source_location_id)
            && $transfer->sourceLocation->type === LocationType::Warehouse
            && in_array($transfer->status, [TransferStatus::Approved, TransferStatus::PartiallyDispatched, TransferStatus::PartiallyReceived], true);
    }

    public function receive(User $user, Transfer $transfer): bool
    {
        return $user->hasPermission('transfers.receive')
            && $user->canAccessLocation($transfer->destination_location_id)
            && in_array($transfer->status, [TransferStatus::Dispatched, TransferStatus::PartiallyDispatched, TransferStatus::PartiallyReceived], true);
    }

    public function closeVariance(User $user, Transfer $transfer): bool
    {
        return $user->hasPermission('transfers.close_variance')
            && $user->isAdmin()
            && in_array($transfer->status, [TransferStatus::Received, TransferStatus::PartiallyReceived], true);
    }
}
