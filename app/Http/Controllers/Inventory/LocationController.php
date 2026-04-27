<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('stores.view'), 403);

        return Inertia::render('reports/stores', [
            'locations' => Location::query()
                ->withCount(['stocks', 'orders', 'invoices'])
                ->orderBy('type')
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }
}
