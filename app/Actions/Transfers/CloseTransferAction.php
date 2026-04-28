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

class CloseTransferAction
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function __invoke(Transfer $transfer, User $actor, ?string $notes = null): Transfer
    {
        if (! in_array($transfer->status, [TransferStatus::Received, TransferStatus::PartiallyReceived], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only received transfers can be closed.',
            ]);
        }

        $fromStatus = $transfer->status;
        $transfer->loadMissing('items.productVariant.product', 'destinationLocation');

        return DB::transaction(function () use ($transfer, $actor, $notes, $fromStatus) {
            $hasVariance = false;

            foreach ($transfer->items as $item) {
                $outstanding = $item->dispatched_quantity - $item->received_quantity;

                if ($outstanding <= 0) {
                    continue;
                }

                $this->stockService->deductStock(
                    actor: $actor,
                    variant: $item->productVariant,
                    sourceLocation: $transfer->destinationLocation,
                    sourceBucket: StockBucket::InTransit,
                    quantity: $outstanding,
                    movementType: StockMovementType::TransferVariance,
                    reference: $transfer,
                    notes: $notes ?: 'Variance closeout for '.$transfer->code,
                    meta: ['transfer_code' => $transfer->code, 'transfer_item_id' => $item->id],
                );

                $item->update(['variance_quantity' => $outstanding]);
                $hasVariance = true;
            }

            $newStatus = $hasVariance ? TransferStatus::ClosedWithVariance : TransferStatus::Closed;

            $transfer->update([
                'status' => $newStatus,
                'has_variance' => $hasVariance,
                'closed_by' => $actor->id,
                'closed_at' => now(),
                'notes' => $notes ?: $transfer->notes,
            ]);

            TransferStatusHistory::create([
                'transfer_id' => $transfer->id,
                'from_status' => $fromStatus->value,
                'to_status' => $newStatus->value,
                'acted_by' => $actor->id,
                'reason' => $hasVariance ? 'Transfer closed with variance' : 'Transfer closed',
            ]);

            $this->auditLogService->record(
                $actor,
                'transfer',
                $hasVariance ? 'transfer.closed_with_variance' : 'transfer.closed',
                'Closed transfer '.$transfer->code,
                $transfer,
                $transfer->destinationLocation,
                ['transfer_code' => $transfer->code, 'has_variance' => $hasVariance],
            );

            return $transfer->fresh(['items.productVariant.product', 'sourceLocation', 'destinationLocation', 'requester', 'receiver', 'closer']);
        });
    }
}
