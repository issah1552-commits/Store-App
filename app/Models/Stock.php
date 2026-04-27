<?php

namespace App\Models;

use App\Enums\StockBucket;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'product_variant_id',
        'bucket',
        'quantity',
        'reserved_quantity',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'bucket' => StockBucket::class,
            'quantity' => 'integer',
            'reserved_quantity' => 'integer',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
