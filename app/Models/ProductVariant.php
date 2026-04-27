<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'color',
        'meter_length',
        'standard_cost_tzs',
        'wholesale_price_tzs',
        'retail_price_tzs',
        'low_stock_threshold',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'meter_length' => 'decimal:2',
            'standard_cost_tzs' => 'decimal:2',
            'wholesale_price_tzs' => 'decimal:2',
            'retail_price_tzs' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
