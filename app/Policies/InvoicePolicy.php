<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.view')
            && $user->canAccessLocation($invoice->location_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('invoices.create') && $user->isShopUser();
    }

    public function markPaid(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.mark_paid')
            && $user->canAccessLocation($invoice->location_id)
            && $invoice->status === InvoiceStatus::Issued;
    }

    public function void(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.void')
            && $user->canAccessLocation($invoice->location_id)
            && $invoice->status === InvoiceStatus::Issued;
    }
}
