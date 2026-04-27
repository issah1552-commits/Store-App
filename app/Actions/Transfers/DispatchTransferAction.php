<?php

namespace App\Actions\Transfers;

use App\Enums\StockBucket;
use App\Enums\StockMovementType;
use App\Enums\TransferStatus;
use App\Models\Transfer;
use App\Models\TransferStatusHistory;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DispatchTransferAction
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function __invoke(Transfer $transfer, User $actor, array $dispatchLines, ?string $notes = null): Transfer
    {
        if (! in_array($transfer->status, [TransferStatus::Approved, TransferStatus::PartiallyDispatched, TransferStatus::PartiallyReceived], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only approved transfers can be dispatched.',
            ]);
        }

        $fromStatus = $transfer->status;
        $transfer->loadMissing('items.productVariant.product', 'sourceLocation', 'destinationLocation');
        $dispatchMap = collect($dispatchLines)->keyBy('product_variant_id');

        return DB::transaction(function () use ($transfer, $actor, $dispatchMap, $notes, $fromStatus) {
            $totalDispatched = 0;

            foreach ($transfer->items as $item) {
                $approvedQuantity = $item->approved_quantity ?: $item->requested_quantity;
                $remaining = $approvedQuantity - $item->dispatched_quantity;

                if ($remaining <= 0) {
                    continue;
                }

                $dispatchQuantity = (int) ($dispatchMap[$item->product_variant_id]['quantity'] ?? $remaining);

                if ($dispatchQuantity === 0) {
                    continue;
                }

                if ($dispatchQuantity < 0 || $dispatchQuantity > $remaining) {
                    throw ValidationException::withMessages([
                        'items' => 'Dispatch quantity is invalid for variant '.$item->productVariant->sku.'.',
                    ]);
                }

                $this->stockService->moveStock(
                    actor: $actor,
                    variant: $item->productVariant,
                    sourceLocation: $transfer->sourceLocation,
                    sourceBucket: StockBucket::Warehouse,
                    destinationLocation: $transfer->destinationLocation,
                    destinationBucket: StockBucket::InTransit,
                    quantity: $dispatchQuantity,
                    movementType: StockMovementType::TransferDispatch,
                    reference: $transfer,
                    notes: $notes ?: 'Warehouse dispatch for '.$transfer->code,
                    meta: ['transfer_code' => $transfer->code, 'transfer_item_id' => $item->id],
                );

                $item->increment('dispatched_quantity', $dispatchQuantity);
                $totalDispatched += $dispatchQuantity;
            }

            if ($totalDispatched === 0) {
                throw ValidationException::withMessages([
                    'items' => 'No dispatch quantities were provided.',
                ]);
            }

            $transfer->refresh()->load('items');
            $isFullyDispatched = $transfer->items->every(function ($item) {
                $approved = $item->approved_quantity ?: $item->requested_quantity;

                return $item->dispatched_quantity >= $approved;
            });
            $hasAnyReceipts = $transfer->items->sum('received_quantity') > 0;

            $newStatus = $isFullyDispatched
                ? ($hasAnyReceipts ? TransferStatus::PartiallyReceived : TransferStatus::Dispatched)
                : ($hasAnyReceipts ? TransferStatus::PartiallyReceived : TransferStatus::PartiallyDispatched);

            $transfer->update([
                'status' => $newStatus,
                'dispatched_by' => $actor->id,
                'dispatched_at' => now(),
                'notes' => $notes ?: $transfer->notes,
            ]);

            TransferStatusHistory::create([
                'transfer_id' => $transfer->id,
                'from_status' => $fromStatus->value,
                'to_status' => $newStatus->value,
                'acted_by' => $actor->id,
                'reason' => 'Transfer dispatched from warehouse',
            ]);

            $this->auditLogService->record(
                $actor,
                'transfer',
                'transfer.dispatched',
                'Dispatched transfer '.$transfer->code,
                $transfer,
                $transfer->sourceLocation,
                ['transfer_code' => $transfer->code, 'total_dispatched' => $totalDispatched],
            );

            return $transfer->fresh(['items.productVariant.product', 'sourceLocation', 'destinationLocation', 'requester', 'approver', 'dispatcher']);
        });
    }
}
