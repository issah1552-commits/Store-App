<?php

namespace App\Actions\InternalMovements;

use App\Enums\StockBucket;
use App\Enums\StockMovementType;
use App\Enums\InternalMovementStatus;
use App\Models\InternalMovement;
use App\Models\InternalMovementHistory;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveInternalMovementAction
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function __invoke(InternalMovement $movement, User $actor, ?string $notes = null): InternalMovement
    {
        if ($movement->status !== InternalMovementStatus::Escalated) {
            throw ValidationException::withMessages([
                'status' => 'Only escalated movements can be approved.',
            ]);
        }

        $movement->loadMissing('items.productVariant.product', 'location');

        return DB::transaction(function () use ($movement, $actor, $notes) {
            $movement->update([
                'status' => InternalMovementStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'notes' => $notes ?: $movement->notes,
            ]);

            InternalMovementHistory::create([
                'internal_movement_id' => $movement->id,
                'from_status' => InternalMovementStatus::Escalated->value,
                'to_status' => InternalMovementStatus::Approved->value,
                'acted_by' => $actor->id,
                'reason' => 'Escalated movement approved',
            ]);

            foreach ($movement->items as $item) {
                $this->stockService->moveStock(
                    actor: $actor,
                    variant: $item->productVariant,
                    sourceLocation: $movement->location,
                    sourceBucket: StockBucket::Wholesale,
                    destinationLocation: $movement->location,
                    destinationBucket: StockBucket::Retail,
                    quantity: $item->quantity,
                    movementType: StockMovementType::InternalMovement,
                    reference: $movement,
                    notes: 'Approved internal movement execution',
                    meta: ['internal_movement_code' => $movement->code, 'internal_movement_item_id' => $item->id],
                );
            }

            $movement->update([
                'status' => InternalMovementStatus::Completed,
                'completed_by' => $actor->id,
                'completed_at' => now(),
            ]);

            InternalMovementHistory::create([
                'internal_movement_id' => $movement->id,
                'from_status' => InternalMovementStatus::Approved->value,
                'to_status' => InternalMovementStatus::Completed->value,
                'acted_by' => $actor->id,
                'reason' => 'Approved movement posted to stock',
            ]);

            $this->auditLogService->record(
                $actor,
                'internal_movement',
                'internal_movement.approved',
                'Approved internal movement '.$movement->code,
                $movement,
                $movement->location,
                ['code' => $movement->code],
            );

            return $movement->fresh(['items.productVariant.product', 'location', 'requester', 'approver', 'completer']);
        });
    }
}
