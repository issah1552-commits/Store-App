<?php

namespace App\Policies;

use App\Enums\LocationType;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.view')
            && $user->canAccessLocation($order->location_id)
            && $order->location->type === LocationType::Shop;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('orders.create') && $user->isShopUser();
    }

    public function complete(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.complete')
            && $user->canAccessLocation($order->location_id)
            && $order->status === OrderStatus::Pending;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->hasPermission('orders.cancel')
            && $user->canAccessLocation($order->location_id)
            && $order->status === OrderStatus::Pending;
    }
}
