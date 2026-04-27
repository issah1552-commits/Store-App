<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\StockBucket;
use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteOrderAction
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function __invoke(Order $order, User $actor): Order
    {
        if ($order->status !== OrderStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Only pending orders can be completed.',
            ]);
        }

        $order->loadMissing('items.productVariant.product', 'location');

        return DB::transaction(function () use ($order, $actor) {
            foreach ($order->items as $item) {
                $this->stockService->deductStock(
                    actor: $actor,
                    variant: $item->productVariant,
                    sourceLocation: $order->location,
                    sourceBucket: StockBucket::Retail,
                    quantity: $item->quantity,
                    movementType: StockMovementType::OrderSale,
                    reference: $order,
                    notes: 'Retail sale completion for '.$order->order_number,
                    meta: ['order_number' => $order->order_number, 'order_item_id' => $item->id],
                );
            }

            $order->update([
                'status' => OrderStatus::Completed,
                'completed_at' => now(),
            ]);

            $this->auditLogService->record(
                $actor,
                'order',
                'order.completed',
                'Completed order '.$order->order_number,
                $order,
                $order->location,
                ['order_number' => $order->order_number],
            );

            return $order->fresh(['items.productVariant.product', 'location', 'orderedBy']);
        });
    }
}
