<?php

namespace App\Models;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role_id',
        'default_location_id',
        'status',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'is_active' => 'boolean',
            'last_activity_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function defaultLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'default_location_id');
    }

    public function locationAssignments(): HasMany
    {
        return $this->hasMany(UserLocationAssignment::class);
    }

    public function assignedLocations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'user_location_assignments')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    public function createdProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'created_by');
    }

    public function updatedProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'updated_by');
    }

    public function hasRole(string|array $role): bool
    {
        $roleName = $this->role?->name;

        if (is_array($role)) {
            return in_array($roleName, $role, true);
        }

        return $roleName === $role;
    }

    public function hasPermission(string $permission): bool
    {
        $override = $this->relationLoaded('permissionOverrides')
            ? $this->permissionOverrides->first(function (UserPermission $userPermission) use ($permission) {
                $permissionName = $userPermission->relationLoaded('permission')
                    ? $userPermission->permission?->name
                    : $userPermission->permission()->value('name');

                return $permissionName === $permission;
            })
            : $this->permissionOverrides()->whereHas('permission', fn ($query) => $query->where('name', $permission))->first();

        if ($override instanceof UserPermission) {
            return $override->allowed;
        }

        $permissions = $this->relationLoaded('role') && $this->role?->relationLoaded('permissions')
            ? $this->role->permissions
            : $this->role?->permissions()->get();

        return (bool) $permissions?->contains(fn (Permission $item) => $item->name === $permission);
    }

    public function canAccessLocation(Location|int|null $location): bool
    {
        if ($location === null) {
            return false;
        }

        $locationId = $location instanceof Location ? $location->getKey() : $location;

        if ($this->hasRole('admin')) {
            return true;
        }

        if ((int) $this->default_location_id === (int) $locationId) {
            return true;
        }

        if ($this->relationLoaded('assignedLocations')) {
            return $this->assignedLocations->contains('id', $locationId);
        }

        return $this->assignedLocations()->whereKey($locationId)->exists();
    }

    public function isWarehouseUser(): bool
    {
        return $this->hasRole(['warehouse_manager', 'warehouse_user']);
    }

    public function isShopUser(): bool
    {
        return $this->hasRole(['shop_manager', 'shop_user', 'retail_staff']);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}
