<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\LocationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Locations\LocationStoreRequest;
use App\Models\Location;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('stores.view'), 403);
        $canCreateStores = $request->user()->isAdmin() || $request->user()->hasPermission('stores.create');
        $canManageStores = $request->user()->isAdmin() || $request->user()->hasPermission('stores.shutdown');

        return Inertia::render('reports/stores', [
            'locations' => Location::query()
                ->withCount(['stocks', 'orders', 'invoices'])
                ->orderBy('type')
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'canCreateStores' => $canCreateStores,
            'canManageStores' => $canManageStores,
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->isAdmin() || $request->user()->hasPermission('stores.create'), 403);

        return Inertia::render('reports/store-form');
    }

    public function store(LocationStoreRequest $request, AuditLogService $auditLogService): RedirectResponse
    {
        $location = Location::create([
            'name' => $request->string('name')->toString(),
            'code' => Str::upper($request->string('code')->trim()->toString()),
            'region_name' => $request->input('region_name'),
            'type' => $request->enum('type', LocationType::class)->value,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $auditLogService->record(
            $request->user(),
            'store',
            'store.created',
            'Created '.$location->type->value.' '.$location->name,
            $location,
            $location,
            ['location_code' => $location->code, 'type' => $location->type->value],
            $request,
        );

        return redirect()->route('stores.index')->with('success', 'Location created successfully.');
    }

    public function toggleActive(Location $location, Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->hasPermission('stores.shutdown'), 403);
        abort_unless($location->type === LocationType::Shop, 404);

        $location->update([
            'is_active' => ! $location->is_active,
        ]);

        $auditLogService->record(
            $request->user(),
            'store',
            $location->is_active ? 'store.reopened' : 'store.shutdown',
            ($location->is_active ? 'Reopened store ' : 'Shut down store ').$location->name,
            $location,
            $location,
            ['location_code' => $location->code, 'is_active' => $location->is_active],
            $request,
        );

        return back()->with('success', $location->is_active ? 'Store reopened successfully.' : 'Store shut down successfully.');
    }
}
