<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'order_id',
        'location_id',
        'issued_by',
        'status',
        'payment_status',
        'subtotal_tzs',
        'tax_tzs',
        'discount_tzs',
        'total_tzs',
        'amount_paid_tzs',
        'balance_tzs',
        'issued_at',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal_tzs' => 'decimal:2',
            'tax_tzs' => 'decimal:2',
            'discount_tzs' => 'decimal:2',
            'total_tzs' => 'decimal:2',
            'amount_paid_tzs' => 'decimal:2',
            'balance_tzs' => 'decimal:2',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
