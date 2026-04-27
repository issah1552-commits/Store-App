<?php

namespace App\Enums;

enum StockBucket: string
{
    case Warehouse = 'warehouse';
    case Wholesale = 'wholesale';
    case Retail = 'retail';
    case InTransit = 'in_transit';
}
