<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Location;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrderAction
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function __invoke(User $actor, Location $location, array $payload): Order
    {
        $items = collect($payload['items'] ?? []);

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'At least one order item is required.',
            ]);
        }

        $variants = ProductVariant::query()->whereIn('id', $items->pluck('product_variant_id'))->get()->keyBy('id');

        return DB::transaction(function () use ($actor, $location, $payload, $items, $variants) {
            $subtotal = 0;

            $order = Order::create([
                'order_number' => 'ORD-'.str($location->code)->after('SHOP-')->upper()->value().'-'.Str::upper(Str::random(6)),
                'location_id' => $location->id,
                'ordered_by' => $actor->id,
                'status' => OrderStatus::Pending,
                'customer_name' => $payload['customer_name'] ?? null,
                'customer_phone' => $payload['customer_phone'] ?? null,
                'discount_tzs' => $payload['discount_tzs'] ?? 0,
                'notes' => $payload['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $variant = $variants->get($item['product_variant_id']);

                if (! $variant || (int) $item['quantity'] <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Order item payload is invalid.',
                    ]);
                }

                $lineTotal = (int) $item['quantity'] * (float) $variant->retail_price_tzs;
                $subtotal += $lineTotal;

                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'quantity' => (int) $item['quantity'],
                    'unit_price_tzs' => $variant->retail_price_tzs,
                    'line_total_tzs' => $lineTotal,
                ]);
            }

            $total = max($subtotal - (float) ($payload['discount_tzs'] ?? 0), 0);

            $order->update([
                'subtotal_tzs' => $subtotal,
                'total_tzs' => $total,
            ]);

            $this->auditLogService->record(
                $actor,
                'order',
                'order.created',
                'Created order '.$order->order_number,
                $order,
                $location,
                ['order_number' => $order->order_number],
            );

            return $order->fresh(['items.productVariant.product', 'location', 'orderedBy']);
        });
    }
}
