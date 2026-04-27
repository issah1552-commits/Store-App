<?php

namespace App\Http\Controllers\Reports;

use App\Enums\StockBucket;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\InternalMovement;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Stock;
use App\Models\Transfer;
use App\Services\LocationContextService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request, LocationContextService $locationContext): Response
    {
        abort_unless($request->user()->hasPermission('reports.view'), 403);

        $type = $request->string('type')->toString() ?: 'stock';
        $user = $request->user();
        $locationIds = $locationContext->scopedLocationIds($request, $user);

        $dataset = match ($type) {
            'transfers' => Transfer::query()
                ->with(['sourceLocation:id,name', 'destinationLocation:id,name', 'requester:id,name'])
                ->where(function ($query) use ($locationIds) {
                    $query->where(function ($inner) use ($locationIds) {
                        $inner->whereIn('source_location_id', $locationIds)->orWhereIn('destination_location_id', $locationIds);
                    });
                })
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'internal_movements' => InternalMovement::query()
                ->with(['location:id,name', 'requester:id,name'])
                ->whereIn('location_id', $locationIds)
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'orders' => Order::query()
                ->with(['location:id,name', 'orderedBy:id,name'])
                ->whereIn('location_id', $locationIds)
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'invoices' => Invoice::query()
                ->with(['location:id,name', 'issuer:id,name'])
                ->whereIn('location_id', $locationIds)
                ->latest('issued_at')
                ->paginate(15)
                ->withQueryString(),
            'audit' => AuditLog::query()
                ->with(['user:id,name', 'location:id,name'])
                ->where(function ($query) use ($locationIds) {
                    $query->whereNull('location_id')
                        ->orWhereIn('location_id', $locationIds);
                })
                ->latest('created_at')
                ->paginate(20)
                ->withQueryString(),
            default => Stock::query()
                ->with(['location:id,name', 'productVariant.product'])
                ->whereIn('location_id', $locationIds)
                ->when($request->string('bucket')->toString(), fn ($query, $bucket) => $query->where('bucket', $bucket))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        };

        return Inertia::render('reports/index', [
            'reportType' => $type,
            'dataset' => $dataset,
            'reportTypes' => [
                ['label' => 'Stock', 'value' => 'stock'],
                ['label' => 'Transfers', 'value' => 'transfers'],
                ['label' => 'Internal Movements', 'value' => 'internal_movements'],
                ['label' => 'Orders', 'value' => 'orders'],
                ['label' => 'Invoices', 'value' => 'invoices'],
                ['label' => 'Audit', 'value' => 'audit'],
            ],
            'buckets' => collect(StockBucket::cases())->map(fn ($bucket) => ['label' => str($bucket->value)->headline()->toString(), 'value' => $bucket->value]),
            'filters' => $request->only(['type', 'bucket']),
        ]);
    }
}
