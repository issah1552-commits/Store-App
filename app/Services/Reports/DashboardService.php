<?php

namespace App\Services\Reports;

use App\Enums\LocationType;
use App\Enums\StockBucket;
use App\Enums\TransferStatus;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\User;

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
            'cards' => [
                ['label' => 'Total Products', 'value' => Product::query()->count()],
                ['label' => 'Total Shops', 'value' => Location::query()->where('type', LocationType::Shop)->where('is_active', true)->count()],
                ['label' => 'Open Transfers', 'value' => Transfer::query()->whereNotIn('status', [TransferStatus::Closed, TransferStatus::ClosedWithVariance, TransferStatus::Rejected, TransferStatus::Cancelled])->count()],
                ['label' => 'Invoices', 'value' => Invoice::query()->count()],
            ],
            'alerts' => [
                'low_stock' => $this->lowStockQuery()->take(8)->get(),
                'out_of_stock' => $this->outOfStockQuery()->take(8)->get(),
                'variance' => Transfer::query()->where('has_variance', true)->latest('closed_at')->take(5)->get(['id', 'code', 'status', 'closed_at']),
            ],
            'summary' => [
                'warehouse_stock' => Stock::query()->where('bucket', StockBucket::Warehouse)->sum('quantity'),
                'order_stats' => Order::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
                'invoice_stats' => Invoice::query()->selectRaw('payment_status, COUNT(*) as total')->groupBy('payment_status')->pluck('total', 'payment_status'),
            ],
        ];
    }

    protected function warehouseLocationMetrics(Location $location): array
    {
        $locationId = $location->getKey();
        $stockBuckets = [StockBucket::Warehouse->value, StockBucket::InTransit->value];

        return [
            'cards' => [
                ['label' => 'Products in Warehouse', 'value' => $this->productCountForLocation($locationId, $stockBuckets)],
                ['label' => 'Warehouse Stock', 'value' => $this->stockSumForLocation($locationId, [StockBucket::Warehouse->value])],
                ['label' => 'Pending Approvals', 'value' => Transfer::query()->where('source_location_id', $locationId)->where('status', TransferStatus::PendingApproval)->count()],
                ['label' => 'Awaiting Receipt', 'value' => Transfer::query()->where('source_location_id', $locationId)->whereIn('status', [TransferStatus::Dispatched, TransferStatus::PartiallyReceived])->count()],
            ],
            'alerts' => [
                'low_stock' => $this->lowStockQuery()
                    ->where('stocks.location_id', $locationId)
                    ->whereIn('stocks.bucket', $stockBuckets)
                    ->take(8)
                    ->get(),
                'out_of_stock' => $this->outOfStockQuery()
                    ->where('stocks.location_id', $locationId)
                    ->whereIn('stocks.bucket', $stockBuckets)
                    ->take(8)
                    ->get(),
            ],
            'summary' => [
                'warehouse_stock' => $this->stockSumForLocation($locationId, [StockBucket::Warehouse->value]),
            ],
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
            'cards' => [
                ['label' => 'Warehouse Stock', 'value' => Stock::query()->where('location_id', $warehouseId)->where('bucket', StockBucket::Warehouse)->sum('quantity')],
                ['label' => 'Pending Approvals', 'value' => Transfer::query()->where('status', TransferStatus::PendingApproval)->count()],
                ['label' => 'Awaiting Dispatch', 'value' => Transfer::query()->where('status', TransferStatus::Approved)->count()],
                ['label' => 'Awaiting Receipt', 'value' => Transfer::query()->whereIn('status', [TransferStatus::Dispatched, TransferStatus::PartiallyReceived])->count()],
            ],
            'alerts' => [
                'low_stock' => $this->lowStockQuery()->where('stocks.location_id', $warehouseId)->take(8)->get(),
                'out_of_stock' => $this->outOfStockQuery()->where('stocks.location_id', $warehouseId)->take(8)->get(),
            ],
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
        $canViewOrders = $user->isAdmin() || $user->hasPermission('orders.view');
        $canViewInvoices = $user->isAdmin() || $user->hasPermission('invoices.view');

        $metrics = [
            'cards' => [
                ['label' => 'Products in Store', 'value' => $this->productCountForLocation($locationId, $stockBuckets)],
                ['label' => 'Wholesale Stock', 'value' => $this->stockSumForLocation($locationId, [StockBucket::Wholesale->value])],
                ['label' => 'Retail Stock', 'value' => $this->stockSumForLocation($locationId, [StockBucket::Retail->value])],
                [
                    'label' => $canViewOrders ? 'Sales Orders' : 'Incoming Transfers',
                    'value' => $canViewOrders
                        ? Order::query()->where('location_id', $locationId)->count()
                        : Transfer::query()
                            ->where('destination_location_id', $locationId)
                            ->whereIn('status', [TransferStatus::Approved, TransferStatus::Dispatched, TransferStatus::PartiallyReceived])
                            ->count(),
                ],
            ],
            'alerts' => [
                'low_stock' => $this->lowStockQuery()
                    ->where('stocks.location_id', $locationId)
                    ->whereIn('stocks.bucket', $stockBuckets)
                    ->take(8)
                    ->get(),
                'out_of_stock' => $this->outOfStockQuery()
                    ->where('stocks.location_id', $locationId)
                    ->whereIn('stocks.bucket', $stockBuckets)
                    ->take(8)
                    ->get(),
            ],
            'summary' => [],
            'activity' => Transfer::query()
                ->with(['destinationLocation:id,name', 'requester:id,name'])
                ->where('destination_location_id', $locationId)
                ->latest()
                ->take(8)
                ->get(['id', 'code', 'status', 'destination_location_id', 'requested_by', 'created_at']),
        ];

        if ($canViewOrders) {
            $metrics['summary']['order_stats'] = Order::query()
                ->where('location_id', $locationId)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');
        }

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
            'cards' => [
                ['label' => 'Wholesale Stock', 'value' => Stock::query()->where('location_id', $locationId)->where('bucket', StockBucket::Wholesale)->sum('quantity')],
                ['label' => 'Retail Stock', 'value' => Stock::query()->where('location_id', $locationId)->where('bucket', StockBucket::Retail)->sum('quantity')],
                ['label' => 'Incoming Transfers', 'value' => Transfer::query()->where('destination_location_id', $locationId)->whereIn('status', [TransferStatus::Approved, TransferStatus::Dispatched, TransferStatus::PartiallyReceived])->count()],
                ['label' => 'Recent Orders', 'value' => Order::query()->where('location_id', $locationId)->count()],
            ],
            'alerts' => [
                'low_retail_stock' => $this->lowStockQuery()->where('stocks.location_id', $locationId)->where('stocks.bucket', StockBucket::Retail)->take(8)->get(),
                'out_of_stock_retail' => $this->outOfStockQuery()->where('stocks.location_id', $locationId)->where('stocks.bucket', StockBucket::Retail)->take(8)->get(),
            ],
            'invoices' => Invoice::query()->where('location_id', $locationId)->latest()->take(6)->get(['id', 'invoice_number', 'payment_status', 'total_tzs', 'issued_at']),
        ];
    }

    protected function productCountForLocation(int $locationId, array $buckets): int
    {
        return Product::query()
            ->join('product_variants', 'product_variants.product_id', '=', 'products.id')
            ->join('stocks', 'stocks.product_variant_id', '=', 'product_variants.id')
            ->where('products.is_active', true)
            ->where('product_variants.is_active', true)
            ->where('stocks.location_id', $locationId)
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
