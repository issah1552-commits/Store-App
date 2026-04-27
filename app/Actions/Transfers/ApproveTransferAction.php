<?php

namespace App\Actions\Transfers;

use App\Enums\TransferStatus;
use App\Models\Transfer;
use App\Models\TransferStatusHistory;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveTransferAction
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function __invoke(Transfer $transfer, User $actor): Transfer
    {
        if ($transfer->status !== TransferStatus::PendingApproval) {
            throw ValidationException::withMessages([
                'status' => 'Only pending approval transfers can be approved.',
            ]);
        }

        $transfer->loadMissing('items.productVariant.product', 'destinationLocation');

        if ($transfer->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'A transfer must contain at least one item before approval.',
            ]);
        }

        return DB::transaction(function () use ($transfer, $actor) {
            foreach ($transfer->items as $item) {
                $item->approved_quantity = $item->approved_quantity ?: $item->requested_quantity;
                $item->save();
            }

            $transfer->update([
                'status' => TransferStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            TransferStatusHistory::create([
                'transfer_id' => $transfer->id,
                'from_status' => TransferStatus::PendingApproval->value,
                'to_status' => TransferStatus::Approved->value,
                'acted_by' => $actor->id,
                'reason' => 'Approved for dispatch',
            ]);

            $this->auditLogService->record(
                $actor,
                'transfer',
                'transfer.approved',
                'Approved transfer '.$transfer->code,
                $transfer,
                $transfer->destinationLocation,
                ['transfer_code' => $transfer->code],
            );

            return $transfer->fresh(['items.productVariant.product', 'sourceLocation', 'destinationLocation', 'requester', 'approver']);
        });
    }
}
