<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalMovementRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'role_id',
        'category_id',
        'max_quantity_per_item',
        'max_quantity_per_transaction',
        'max_daily_quantity_per_user',
        'after_hours_blocked',
        'after_hours_start',
        'after_hours_end',
        'requires_reason',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'after_hours_blocked' => 'boolean',
            'requires_reason' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
