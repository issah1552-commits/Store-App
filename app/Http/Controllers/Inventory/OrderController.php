<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Orders\CompleteOrderAction;
use App\Actions\Orders\CreateOrderAction;
use App\Enums\StockBucket;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\OrderStoreRequest;
use App\Models\Location;
use App\Models\Order;
use App\Models\Stock;
use App\Services\AuditLogService;
use App\Services\LocationContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request, LocationContextService $locationContext): Response
    {
        $this->authorize('viewAny', Order::class);

        $user = $request->user();
        $selectedLocation = $locationContext->resolveSelectedLocation($request, $user);
        $locationIds = $locationContext->scopedLocationIds($request, $user);

        $orders = Order::query()
            ->with(['location:id,name', 'orderedBy:id,name'])
            ->whereIn('location_id', $locationIds)
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('orders/index', [
            'orders' => $orders,
            'canCreate' => $request->user()->can('create', Order::class) && $selectedLocation?->type !== \App\Enums\LocationType::Warehouse,
        ]);
    }

    public function create(Request $request, LocationContextService $locationContext): Response
    {
        $this->authorize('create', Order::class);

        $selectedLocation = $locationContext->resolveSelectedLocation($request, $request->user());
        abort_if($selectedLocation?->type === \App\Enums\LocationType::Warehouse, 403);

        $locationIds = $locationContext->scopedLocationIds($request, $request->user());

        return Inertia::render('orders/form', [
            'locations' => Location::query()->whereIn('id', $locationIds)->where('type', 'shop')->get(['id', 'name', 'code']),
            'variants' => Stock::query()
                ->with(['productVariant.product'])
                ->whereIn('location_id', $locationIds)
                ->where('bucket', StockBucket::Retail)
                ->where('quantity', '>', 0)
                ->get()
                ->map(fn ($stock) => [
                    'location_id' => $stock->location_id,
                    'product_variant_id' => $stock->product_variant_id,
                    'sku' => $stock->productVariant->sku,
                    'brand_name' => $stock->productVariant->product->brand_name,
                    'color' => $stock->productVariant->color,
                    'meter_length' => $stock->productVariant->meter_length,
                    'price_tzs' => $stock->productVariant->retail_price_tzs,
                    'available_quantity' => $stock->quantity,
                ]),
        ]);
    }

    public function store(OrderStoreRequest $request, CreateOrderAction $action, LocationContextService $locationContext): RedirectResponse
    {
        $this->authorize('create', Order::class);

        $location = Location::query()->findOrFail($request->integer('location_id'));
        $selectedLocation = $locationContext->resolveSelectedLocation($request, $request->user());

        if ($selectedLocation) {
            abort_unless((int) $selectedLocation->getKey() === (int) $location->getKey(), 403);
        }

        abort_unless($request->user()->canAccessLocation($location), 403);

        $order = $action($request->user(), $location, $request->validated());

        return redirect()->route('orders.show', $order)->with('success', 'Order created.');
    }

    public function show(Order $order, Request $request): Response
    {
        $this->authorize('view', $order);

        $order->load(['location:id,name,code', 'orderedBy:id,name', 'items.productVariant.product']);

        return Inertia::render('orders/show', [
            'order' => $order,
            'canComplete' => $request->user()->can('complete', $order),
            'canCancel' => $request->user()->can('cancel', $order),
        ]);
    }

    public function complete(Order $order, Request $request, CompleteOrderAction $action): RedirectResponse
    {
        $this->authorize('complete', $order);
        $action($order, $request->user());

        return back()->with('success', 'Order completed and retail stock updated.');
    }

    public function cancel(Order $order, Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $order->update([
            'status' => \App\Enums\OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $request->input('reason', 'Cancelled by user'),
        ]);

        $auditLogService->record(
            $request->user(),
            'order',
            'order.cancelled',
            'Cancelled order '.$order->order_number,
            $order,
            $order->location,
            ['order_number' => $order->order_number],
            $request,
        );

        return back()->with('success', 'Order cancelled.');
    }
}
