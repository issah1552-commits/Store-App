<?php

namespace App\Enums;

enum StockMovementType: string
{
    case OpeningBalance = 'opening_balance';
    case TransferDispatch = 'transfer_dispatch';
    case TransferReceipt = 'transfer_receipt';
    case TransferVariance = 'transfer_variance';
    case InternalMovement = 'internal_movement';
    case OrderSale = 'order_sale';
    case ManualAdjustment = 'manual_adjustment';
    case Reversal = 'reversal';
}
