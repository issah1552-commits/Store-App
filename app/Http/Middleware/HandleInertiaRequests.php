<?php

namespace App\Http\Middleware;

use App\Services\AppNavigationService;
use App\Services\LocationContextService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user()?->loadMissing([
            'role.permissions:id,name',
            'defaultLocation:id,name,code,type,region_name',
            'assignedLocations:id,name,code,type,region_name',
            'permissionOverrides.permission:id,name',
        ]);
        $selectedLocation = $user ? app(LocationContextService::class)->resolveSelectedLocation($request, $user) : null;

        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'app' => [
                'currency' => 'TZS',
                'timezone' => 'Africa/Dar_es_Salaam',
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'is_active' => $user->is_active,
                    'role' => $user->role ? [
                        'name' => $user->role->name,
                        'display_name' => $user->role->display_name,
                    ] : null,
                    'default_location' => $user->defaultLocation,
                    'assigned_locations' => $user->assignedLocations,
                    'permissions' => $user->role?->permissions?->pluck('name')->values() ?? [],
                ] : null,
            ],
            'navigation' => $user ? app(AppNavigationService::class)->forUser($user) : [],
            'location_context' => [
                'selected_location' => $selectedLocation,
            ],
        ]);
    }
}
