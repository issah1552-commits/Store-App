<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Reports\DashboardService;
use App\Services\LocationContextService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboardService, LocationContextService $locationContext): Response
    {
        $user = $request->user()->loadMissing('assignedLocations');

        abort_unless($user->hasPermission('dashboard.view'), 403);

        $locations = $locationContext->availableLocationsFor($user);
        $selectedLocation = $locationContext->resolveSelectedLocation($request, $user);

        return Inertia::render('dashboard/index', [
            'metrics' => $dashboardService->forUser($user, $selectedLocation),
            'locations' => $locations,
            'filters' => [
                'location_id' => $selectedLocation?->id,
            ],
        ]);
    }
}
