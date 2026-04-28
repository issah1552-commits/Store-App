<?php

namespace App\Services\Reports;

use App\Enums\InvoiceStatus;
use App\Enums\LocationType;
use App\Enums\OrderStatus;
use App\Enums\StockBucket;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\User;
use Carbon\CarbonInterface;

class DashboardService
{
    public function forUser(User $user, ?Location $selectedLocation = null): array
    {
        if ($selectedLocation instanceof Location) {
            return $this->locationMetrics($user, $selectedLocation);
        }

        if ($user->isAdmin()) {
            return $this->adminMetrics();
        }

        if ($user->isWarehouseUser()) {
            return $this->warehouseMetrics($user);
        }

        return $this->shopMetrics($user);
    }

    protected function locationMetrics(User $user, Location $location): array
    {
        return $location->type === LocationType::Warehouse
            ? $this->warehouseLocationMetrics($location)
            : $this->shopLocationMetrics($user, $location);
    }

    protected function adminMetrics(): array
    {
        return [
            'cards' => $this->metricCards(),
            'alerts' => [
                'low_stock' => $this->lowStockQuery()->take(50)->get(),
                'out_of_stock' => $this->outOfStockQuery()->take(50)->get(),
                'variance' => Transfer::query()->where('has_variance', true)->latest('closed_at')->take(5)->get(['id', 'code', 'status', 'closed_at']),
            ],
            'summary' => [
                'warehouse_stock' => Stock::query()->where('bucket', StockBucket::Warehouse)->sum('quantity'),
                'order_stats' => Order::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
                'invoice_stats' => Invoice::query()->selectRaw('payment_status, COUNT(*) as total')->groupBy('payment_status')->pluck('total', 'payment_status'),
            ],
            'charts' => $this->dashboardCharts(),
            'recent_activities' => $this->recentActivities(),
        ];
    }

    protected function warehouseLocationMetrics(Location $location): array
    {
        $locationId = $location->getKey();
        $stockBuckets = [StockBucket::Warehouse->value, StockBucket::InTransit->value];

        return [
            'cards' => $this->metricCards([$locationId], $stockBuckets),
            'alerts' => [
                'low_stock' => $this->lowStockQuery()
                    ->where('stocks.location_id', $locationId)
                    ->whereIn('stocks.bucket', $stockBuckets)
                    ->take(50)
                    ->get(),
                'out_of_stock' => $this->outOfStockQuery()
                    ->where('stocks.location_id', $locationId)
                    ->whereIn('stocks.bucket', $stockBuckets)
                    ->take(50)
                    ->get(),
            ],
            'summary' => [
                'warehouse_stock' => $this->stockSumForLocation($locationId, [StockBucket::Warehouse->value]),
            ],
            'charts' => $this->dashboardCharts([$locationId], $stockBuckets),
            'recent_activities' => $this->recentActivities([$locationId]),
            'activity' => Transfer::query()
                ->with(['destinationLocation:id,name', 'requester:id,name'])
                ->where(function ($query) use ($locationId) {
                    $query->where('source_location_id', $locationId)
                        ->orWhere('destination_location_id', $locationId);
                })
                ->latest()
                ->take(8)
                ->get(['id', 'code', 'status', 'destination_location_id', 'requested_by', 'created_at']),
        ];
    }

    protected function warehouseMetrics(User $user): array
    {
        $warehouseId = $user->default_location_id;

        return [
            'cards' => $this->metricCards([$warehouseId], [StockBucket::Warehouse->value, StockBucket::InTransit->value]),
            'alerts' => [
                'low_stock' => $this->lowStockQuery()->where('stocks.location_id', $warehouseId)->take(50)->get(),
                'out_of_stock' => $this->outOfStockQuery()->where('stocks.location_id', $warehouseId)->take(50)->get(),
            ],
            'charts' => $this->dashboardCharts([$warehouseId], [StockBucket::Warehouse->value, StockBucket::InTransit->value]),
            'recent_activities' => $this->recentActivities([$warehouseId]),
            'activity' => Transfer::query()
                ->with(['destinationLocation:id,name', 'requester:id,name'])
                ->latest()
                ->take(8)
                ->get(['id', 'code', 'status', 'destination_location_id', 'requested_by', 'created_at']),
        ];
    }

    protected function shopLocationMetrics(User $user, Location $location): array
    {
        $locationId = $location->getKey();
        $stockBuckets = [StockBucket::Wholesale->value, StockBucket::Retail->value];
        $canViewInvoices = $user->isAdmin() || $user->hasPermission('invoices.view');

        $metrics = [
            'cards' => $this->metricCards([$locationId], $stockBuckets),
            'alerts' => [
                'low_stock' => $this->lowStockQuery()
                    ->where('stocks.location_id', $locationId)
                    ->whereIn('stocks.bucket', $stockBuckets)
                    ->take(50)
                    ->get(),
                'out_of_stock' => $this->outOfStockQuery()
                    ->where('stocks.location_id', $locationId)
                    ->whereIn('stocks.bucket', $stockBuckets)
                    ->take(50)
                    ->get(),
            ],
            'summary' => [],
            'charts' => $this->dashboardCharts([$locationId], $stockBuckets),
            'recent_activities' => $this->recentActivities([$locationId]),
            'activity' => Transfer::query()
                ->with(['destinationLocation:id,name', 'requester:id,name'])
                ->where('destination_location_id', $locationId)
                ->latest()
                ->take(8)
                ->get(['id', 'code', 'status', 'destination_location_id', 'requested_by', 'created_at']),
        ];

        $metrics['summary']['order_stats'] = Order::query()
            ->where('location_id', $locationId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        if ($canViewInvoices) {
            $metrics['summary']['invoice_stats'] = Invoice::query()
                ->where('location_id', $locationId)
                ->selectRaw('payment_status, COUNT(*) as total')
                ->groupBy('payment_status')
                ->pluck('total', 'payment_status');

            $metrics['invoices'] = Invoice::query()
                ->where('location_id', $locationId)
                ->latest()
                ->take(6)
                ->get(['id', 'invoice_number', 'payment_status', 'total_tzs', 'issued_at']);
        }

        return $metrics;
    }

    protected function shopMetrics(User $user): array
    {
        $locationId = $user->default_location_id;

        return [
            'cards' => $this->metricCards([$locationId], [StockBucket::Wholesale->value, StockBucket::Retail->value]),
            'alerts' => [
                'low_retail_stock' => $this->lowStockQuery()->where('stocks.location_id', $locationId)->where('stocks.bucket', StockBucket::Retail)->take(50)->get(),
                'out_of_stock_retail' => $this->outOfStockQuery()->where('stocks.location_id', $locationId)->where('stocks.bucket', StockBucket::Retail)->take(50)->get(),
            ],
            'charts' => $this->dashboardCharts([$locationId], [StockBucket::Wholesale->value, StockBucket::Retail->value]),
            'recent_activities' => $this->recentActivities([$locationId]),
            'invoices' => Invoice::query()->where('location_id', $locationId)->latest()->take(6)->get(['id', 'invoice_number', 'payment_status', 'total_tzs', 'issued_at']),
        ];
    }

    protected function dashboardCharts(?array $locationIds = null, ?array $stockBuckets = null): array
    {
        return [
            'sales_overview' => $this->salesOverviewByWeekday($locationIds),
            'stock_distribution' => $this->stockDistribution($locationIds, $stockBuckets),
        ];
    }

    protected function metricCards(?array $locationIds = null, ?array $stockBuckets = null): array
    {
        $scopedLocationIds = $this->cleanLocationIds($locationIds);
        $isScoped = $locationIds !== null;
        $buckets = $stockBuckets ?? array_map(fn (StockBucket $bucket) => $bucket->value, StockBucket::cases());

        return [
            [
                'label' => 'Total Products',
                'value' => $isScoped
                    ? $this->productCountForLocations($scopedLocationIds, $buckets)
                    : Product::query()->where('is_active', true)->count(),
            ],
            [
                'label' => 'Total Sales',
                'value' => $this->totalSalesAmount($isScoped ? $scopedLocationIds : null),
            ],
            [
                'label' => 'Low Stock Products',
                'value' => $this->lowStockCount($isScoped ? $scopedLocationIds : null, $isScoped ? $buckets : null),
            ],
            [
                'label' => 'Total Amount Invoiced',
                'value' => $this->totalAmountInvoiced($isScoped ? $scopedLocationIds : null),
            ],
        ];
    }

    protected function totalSalesAmount(?array $locationIds = null): float
    {
        return (float) Order::query()
            ->when($locationIds, fn ($query) => $query->whereIn('location_id', $locationIds))
            ->where('status', OrderStatus::Completed->value)
            ->sum('total_tzs');
    }

    protected function lowStockCount(?array $locationIds = null, ?array $stockBuckets = null): int
    {
        return $this->lowStockQuery()
            ->when($locationIds, fn ($query) => $query->whereIn('stocks.location_id', $locationIds))
            ->when($stockBuckets, fn ($query) => $query->whereIn('stocks.bucket', $stockBuckets))
            ->count('stocks.id');
    }

    protected function totalAmountInvoiced(?array $locationIds = null): float
    {
        return (float) Invoice::query()
            ->when($locationIds, fn ($query) => $query->whereIn('location_id', $locationIds))
            ->where('status', '!=', InvoiceStatus::Void->value)
            ->sum('total_tzs');
    }

    protected function salesOverviewByWeekday(?array $locationIds = null): array
    {
        $weekStart = now()->startOfWeek(CarbonInterface::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(CarbonInterface::SUNDAY);

        $salesByDay = Invoice::query()
            ->when($locationIds, fn ($query) => $query->whereIn('location_id', $locationIds))
            ->where('status', '!=', InvoiceStatus::Void->value)
            ->whereBetween('issued_at', [$weekStart, $weekEnd])
            ->get(['issued_at', 'total_tzs'])
            ->groupBy(fn (Invoice $invoice) => $invoice->issued_at->format('D'))
            ->map(fn ($invoices) => (float) $invoices->sum('total_tzs'));

        return collect(range(0, 6))
            ->map(function (int $dayOffset) use ($weekStart, $salesByDay) {
                $label = $weekStart->copy()->addDays($dayOffset)->format('D');

                return [
                    'label' => $label,
                    'value' => $salesByDay->get($label, 0),
                ];
            })
            ->values()
            ->all();
    }

    protected function stockDistribution(?array $locationIds = null, ?array $stockBuckets = null): array
    {
        if ($locationIds && count($locationIds) === 1) {
            $stockByBucket = Stock::query()
                ->whereIn('location_id', $locationIds)
                ->when($stockBuckets, fn ($query) => $query->whereIn('bucket', $stockBuckets))
                ->selectRaw('bucket, SUM(quantity) as total')
                ->groupBy('bucket')
                ->pluck('total', 'bucket');

            return collect($stockBuckets ?? array_map(fn (StockBucket $bucket) => $bucket->value, StockBucket::cases()))
                ->map(fn (string $bucket) => [
                    'label' => $this->stockBucketLabel($bucket),
                    'value' => (int) $stockByBucket->get($bucket, 0),
                ])
                ->values()
                ->all();
        }

        return Stock::query()
            ->join('locations', 'locations.id', '=', 'stocks.location_id')
            ->when($locationIds, fn ($query) => $query->whereIn('stocks.location_id', $locationIds))
            ->when($stockBuckets, fn ($query) => $query->whereIn('stocks.bucket', $stockBuckets))
            ->selectRaw('locations.name as label, SUM(stocks.quantity) as value')
            ->groupBy('locations.id', 'locations.name')
            ->orderByDesc('value')
            ->take(6)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'value' => (int) $row->value,
            ])
            ->values()
            ->all();
    }

    protected function stockBucketLabel(string $bucket): string
    {
        return match ($bucket) {
            StockBucket::Warehouse->value => 'Warehouse',
            StockBucket::Wholesale->value => 'Wholesale',
            StockBucket::Retail->value => 'Retail',
            StockBucket::InTransit->value => 'In transit',
            default => ucfirst(str_replace('_', ' ', $bucket)),
        };
    }

    protected function recentActivities(?array $locationIds = null): array
    {
        $transfers = Transfer::query()
            ->with(['sourceLocation:id,name', 'destinationLocation:id,name'])
            ->when($locationIds, function ($query) use ($locationIds) {
                $query->where(function ($transferQuery) use ($locationIds) {
                    $transferQuery->whereIn('source_location_id', $locationIds)
                        ->orWhereIn('destination_location_id', $locationIds);
                });
            })
            ->latest()
            ->take(8)
            ->get(['id', 'code', 'status', 'source_location_id', 'destination_location_id', 'created_at', 'updated_at'])
            ->map(function (Transfer $transfer) {
                $source = $transfer->sourceLocation?->name ?? 'Source';
                $destination = $transfer->destinationLocation?->name ?? 'Destination';
                $time = $transfer->updated_at ?? $transfer->created_at;

                return [
                    'id' => 'transfer-'.$transfer->id,
                    'type' => 'Transfer',
                    'description' => 'Transfer '.$transfer->code.' from '.$source.' to '.$destination,
                    'location' => $destination,
                    'time' => $time?->toIso8601String(),
                    'status' => $this->enumValue($transfer->status),
                    'sort_time' => $time?->timestamp ?? 0,
                ];
            });

        $orders = Order::query()
            ->with('location:id,name')
            ->when($locationIds, fn ($query) => $query->whereIn('location_id', $locationIds))
            ->latest()
            ->take(8)
            ->get(['id', 'order_number', 'location_id', 'status', 'completed_at', 'created_at', 'updated_at'])
            ->map(function (Order $order) {
                $time = $order->completed_at ?? $order->updated_at ?? $order->created_at;

                return [
                    'id' => 'order-'.$order->id,
                    'type' => 'Order',
                    'description' => 'Order '.$order->order_number.' '.$this->activityVerb($this->enumValue($order->status)),
                    'location' => $order->location?->name ?? 'Location pending',
                    'time' => $time?->toIso8601String(),
                    'status' => $this->enumValue($order->status),
                    'sort_time' => $time?->timestamp ?? 0,
                ];
            });

        $invoices = Invoice::query()
            ->with('location:id,name')
            ->when($locationIds, fn ($query) => $query->whereIn('location_id', $locationIds))
            ->latest('issued_at')
            ->take(8)
            ->get(['id', 'invoice_number', 'location_id', 'payment_status', 'issued_at', 'created_at', 'updated_at'])
            ->map(function (Invoice $invoice) {
                $time = $invoice->issued_at ?? $invoice->updated_at ?? $invoice->created_at;

                return [
                    'id' => 'invoice-'.$invoice->id,
                    'type' => 'Invoice',
                    'description' => 'Invoice '.$invoice->invoice_number.' '.$this->activityVerb($this->enumValue($invoice->payment_status)),
                    'location' => $invoice->location?->name ?? 'Location pending',
                    'time' => $time?->toIso8601String(),
                    'status' => $this->enumValue($invoice->payment_status),
                    'sort_time' => $time?->timestamp ?? 0,
                ];
            });

        return $transfers
            ->concat($orders)
            ->concat($invoices)
            ->sortByDesc('sort_time')
            ->take(5)
            ->values()
            ->map(function (array $activity) {
                unset($activity['sort_time']);

                return $activity;
            })
            ->all();
    }

    protected function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum ? $value->value : (string) $value;
    }

    protected function activityVerb(string $status): string
    {
        return match ($status) {
            'completed', 'closed', 'paid' => 'completed',
            'cancelled', 'rejected', 'void' => 'cancelled',
            default => 'created',
        };
    }

    protected function cleanLocationIds(?array $locationIds): array
    {
        return array_values(array_filter($locationIds ?? [], fn ($locationId) => $locationId !== null));
    }

    protected function productCountForLocation(int $locationId, array $buckets): int
    {
        return $this->productCountForLocations([$locationId], $buckets);
    }

    protected function productCountForLocations(array $locationIds, array $buckets): int
    {
        return Product::query()
            ->join('product_variants', 'product_variants.product_id', '=', 'products.id')
            ->join('stocks', 'stocks.product_variant_id', '=', 'product_variants.id')
            ->where('products.is_active', true)
            ->where('product_variants.is_active', true)
            ->whereIn('stocks.location_id', $locationIds)
            ->whereIn('stocks.bucket', $buckets)
            ->distinct()
            ->count('products.id');
    }

    protected function stockSumForLocation(int $locationId, array $buckets): int
    {
        return (int) Stock::query()
            ->where('location_id', $locationId)
            ->whereIn('bucket', $buckets)
            ->sum('quantity');
    }

    protected function lowStockQuery()
    {
        return Stock::query()
            ->join('product_variants', 'product_variants.id', '=', 'stocks.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->join('locations', 'locations.id', '=', 'stocks.location_id')
            ->whereColumn('stocks.quantity', '<=', 'product_variants.low_stock_threshold')
            ->where('stocks.quantity', '>', 0)
            ->select([
                'stocks.id',
                'stocks.quantity',
                'stocks.bucket',
                'product_variants.sku',
                'product_variants.color',
                'product_variants.meter_length',
                'product_variants.low_stock_threshold',
                'products.brand_name',
                'locations.name as location_name',
            ]);
    }

    protected function outOfStockQuery()
    {
        return Stock::query()
            ->join('product_variants', 'product_variants.id', '=', 'stocks.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->join('locations', 'locations.id', '=', 'stocks.location_id')
            ->where('stocks.quantity', 0)
            ->select([
                'stocks.id',
                'stocks.bucket',
                'product_variants.sku',
                'product_variants.color',
                'product_variants.meter_length',
                'products.brand_name',
                'locations.name as location_name',
            ]);
    }
}
