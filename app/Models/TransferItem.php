<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'product_variant_id',
        'requested_quantity',
        'approved_quantity',
        'dispatched_quantity',
        'received_quantity',
        'variance_quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_quantity' => 'integer',
            'approved_quantity' => 'integer',
            'dispatched_quantity' => 'integer',
            'received_quantity' => 'integer',
            'variance_quantity' => 'integer',
        ];
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
