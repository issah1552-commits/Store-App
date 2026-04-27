<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalMovementHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_movement_id',
        'from_status',
        'to_status',
        'acted_by',
        'reason',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function internalMovement(): BelongsTo
    {
        return $this->belongsTo(InternalMovement::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
