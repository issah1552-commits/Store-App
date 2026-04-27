<?php

namespace App\Enums;

enum TransferStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case PartiallyDispatched = 'partially_dispatched';
    case Dispatched = 'dispatched';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case ClosedWithVariance = 'closed_with_variance';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
