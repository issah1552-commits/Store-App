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

class ConfirmTransferReceiptAction
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function __invoke(Transfer $transfer, User $actor, array $receiptLines, ?string $notes = null): Transfer
    {
        if (! in_array($transfer->status, [TransferStatus::Dispatched, TransferStatus::PartiallyDispatched, TransferStatus::PartiallyReceived], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only dispatched transfers can be received.',
            ]);
        }

        $fromStatus = $transfer->status;
        $transfer->loadMissing('items.productVariant.product', 'destinationLocation');
        $receiptMap = collect($receiptLines)->keyBy('product_variant_id');

        return DB::transaction(function () use ($transfer, $actor, $receiptMap, $notes, $fromStatus) {
            $totalReceived = 0;

            foreach ($transfer->items as $item) {
                $remaining = $item->dispatched_quantity - $item->received_quantity;

                if ($remaining <= 0) {
                    continue;
                }

                $receivedQuantity = (int) ($receiptMap[$item->product_variant_id]['quantity'] ?? $remaining);

                if ($receivedQuantity === 0) {
                    continue;
                }

                if ($receivedQuantity < 0 || $receivedQuantity > $remaining) {
                    throw ValidationException::withMessages([
                        'items' => 'Received quantity is invalid for variant '.$item->productVariant->sku.'.',
                    ]);
                }

                $this->stockService->moveStock(
                    actor: $actor,
                    variant: $item->productVariant,
                    sourceLocation: $transfer->destinationLocation,
                    sourceBucket: StockBucket::InTransit,
                    destinationLocation: $transfer->destinationLocation,
                    destinationBucket: StockBucket::Wholesale,
                    quantity: $receivedQuantity,
                    movementType: StockMovementType::TransferReceipt,
                    reference: $transfer,
                    notes: $notes ?: 'Transfer receipt confirmation for '.$transfer->code,
                    meta: ['transfer_code' => $transfer->code, 'transfer_item_id' => $item->id],
                );

                $item->increment('received_quantity', $receivedQuantity);
                $totalReceived += $receivedQuantity;
            }

            if ($totalReceived === 0) {
                throw ValidationException::withMessages([
                    'items' => 'No receipt quantities were provided.',
                ]);
            }

            $transfer->refresh()->load('items');
            $isFullyReceived = $transfer->items->every(function ($item) {
                $approved = $item->approved_quantity ?: $item->requested_quantity;

                return $item->dispatched_quantity >= $approved && $item->received_quantity >= $approved;
            });

            $newStatus = $isFullyReceived ? TransferStatus::Closed : TransferStatus::PartiallyReceived;
            $timestamp = now();
            $updates = [
                'status' => $newStatus,
                'received_by' => $actor->id,
                'received_at' => $timestamp,
                'notes' => $notes ?: $transfer->notes,
            ];

            if ($isFullyReceived) {
                $updates['closed_by'] = $actor->id;
                $updates['closed_at'] = $timestamp;
                $updates['has_variance'] = false;
            }

            $transfer->update($updates);

            TransferStatusHistory::create([
                'transfer_id' => $transfer->id,
                'from_status' => $fromStatus->value,
                'to_status' => $newStatus->value,
                'acted_by' => $actor->id,
                'reason' => $isFullyReceived ? 'Transfer receipt confirmed and closed' : 'Transfer receipt confirmed',
            ]);

            $this->auditLogService->record(
                $actor,
                'transfer',
                $isFullyReceived ? 'transfer.received_and_closed' : 'transfer.received',
                ($isFullyReceived ? 'Confirmed receipt and closed transfer ' : 'Confirmed receipt for transfer ').$transfer->code,
                $transfer,
                $transfer->destinationLocation,
                ['transfer_code' => $transfer->code, 'total_received' => $totalReceived, 'auto_closed' => $isFullyReceived],
            );

            return $transfer->fresh(['items.productVariant.product', 'sourceLocation', 'destinationLocation', 'requester', 'receiver', 'closer']);
        });
    }
}
