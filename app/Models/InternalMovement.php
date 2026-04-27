<?php

namespace App\Models;

use App\Enums\InternalMovementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'location_id',
        'status',
        'requested_by',
        'approved_by',
        'completed_by',
        'rejected_by',
        'approved_at',
        'completed_at',
        'rejected_at',
        'total_quantity',
        'total_value_tzs',
        'escalation_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => InternalMovementStatus::class,
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'total_quantity' => 'integer',
            'total_value_tzs' => 'decimal:2',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InternalMovementItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(InternalMovementHistory::class);
    }
}
