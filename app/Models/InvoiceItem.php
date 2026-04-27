<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'product_variant_id',
        'description',
        'quantity',
        'unit_price_tzs',
        'line_total_tzs',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_tzs' => 'decimal:2',
            'line_total_tzs' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
