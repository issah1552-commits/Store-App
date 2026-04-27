<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Issued = 'issued';
    case Void = 'void';
}
