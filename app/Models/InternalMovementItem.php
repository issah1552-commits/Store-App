<?php

namespace App\Models;

use App\Enums\StockBucket;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalMovementItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_movement_id',
        'product_variant_id',
        'source_bucket',
        'destination_bucket',
        'quantity',
        'unit_value_tzs',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'source_bucket' => StockBucket::class,
            'destination_bucket' => StockBucket::class,
            'quantity' => 'integer',
            'unit_value_tzs' => 'decimal:2',
        ];
    }

    public function internalMovement(): BelongsTo
    {
        return $this->belongsTo(InternalMovement::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
