<?php

namespace App\Enums;

enum InternalMovementStatus: string
{
    case Draft = 'draft';
    case Escalated = 'escalated';
    case Approved = 'approved';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Reversed = 'reversed';
}
