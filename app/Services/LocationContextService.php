<?php

namespace App\Services;

use App\Enums\LocationType;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LocationContextService
{
    public function availableLocationIdsFor(User $user): Collection
    {
        if ($user->isAdmin()) {
            return Location::query()
                ->where('is_active', true)
                ->pluck('id');
        }

        $locationIds = $user->assignedLocations()
            ->pluck('locations.id')
            ->push($user->default_location_id)
            ->filter()
            ->unique()
            ->values();

        return Location::query()
            ->where('is_active', true)
            ->whereIn('id', $locationIds)
            ->pluck('id');
    }

    public function availableLocationsFor(User $user): Collection
    {
        return Location::query()
            ->where('is_active', true)
            ->whereIn('id', $this->availableLocationIdsFor($user))
            ->orderByRaw('CASE WHEN type = ? THEN 0 ELSE 1 END', [LocationType::Warehouse->value])
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type', 'region_name']);
    }

    public function resolveSelectedLocation(Request $request, User $user): ?Location
    {
        if ($request->query->has('location_id')) {
            return $this->storeExplicitSelection($request, $user);
        }

        return $this->selectedLocationFromSession($request, $user);
    }

    public function scopedLocationIds(Request $request, User $user): Collection
    {
        $selectedLocation = $this->resolveSelectedLocation($request, $user);

        return $selectedLocation
            ? collect([$selectedLocation->getKey()])
            : $this->availableLocationIdsFor($user);
    }

    protected function storeExplicitSelection(Request $request, User $user): ?Location
    {
        $rawLocationId = $request->query('location_id');

        if ($rawLocationId === null || $rawLocationId === '' || $rawLocationId === 'all') {
            $request->session()->forget('selected_location_id');

            return null;
        }

        $location = Location::query()
            ->whereIn('id', $this->availableLocationIdsFor($user))
            ->whereKey((int) $rawLocationId)
            ->first();

        abort_unless($location, 403);

        $request->session()->put('selected_location_id', $location->getKey());

        return $location;
    }

    protected function selectedLocationFromSession(Request $request, User $user): ?Location
    {
        $selectedLocationId = $request->session()->get('selected_location_id');

        if (! $selectedLocationId) {
            return null;
        }

        $location = Location::query()
            ->whereIn('id', $this->availableLocationIdsFor($user))
            ->whereKey((int) $selectedLocationId)
            ->first();

        if (! $location) {
            $request->session()->forget('selected_location_id');
        }

        return $location;
    }
}
