<?php

namespace App\Models;

use App\Enums\StockBucket;
use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'movement_type',
        'product_variant_id',
        'source_location_id',
        'source_bucket',
        'source_quantity_before',
        'source_quantity_after',
        'destination_location_id',
        'destination_bucket',
        'destination_quantity_before',
        'destination_quantity_after',
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
        'meta',
        'performed_by',
        'reversed_by',
        'reversed_at',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'movement_type' => StockMovementType::class,
            'source_bucket' => StockBucket::class,
            'destination_bucket' => StockBucket::class,
            'meta' => 'array',
            'occurred_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'source_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
