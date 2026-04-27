<?php

namespace App\Actions\InternalMovements;

use App\Enums\InternalMovementStatus;
use App\Enums\StockBucket;
use App\Enums\StockMovementType;
use App\Models\InternalMovement;
use App\Models\InternalMovementHistory;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReverseInternalMovementAction
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function __invoke(InternalMovement $movement, User $actor, ?string $notes = null): InternalMovement
    {
        if ($movement->status !== InternalMovementStatus::Completed) {
            throw ValidationException::withMessages([
                'status' => 'Only completed movements can be reversed.',
            ]);
        }

        $movement->loadMissing('items.productVariant.product', 'location');

        return DB::transaction(function () use ($movement, $actor, $notes) {
            foreach ($movement->items as $item) {
                $this->stockService->moveStock(
                    actor: $actor,
                    variant: $item->productVariant,
                    sourceLocation: $movement->location,
                    sourceBucket: StockBucket::Retail,
                    destinationLocation: $movement->location,
                    destinationBucket: StockBucket::Wholesale,
                    quantity: $item->quantity,
                    movementType: StockMovementType::Reversal,
                    reference: $movement,
                    notes: $notes ?: 'Controlled reversal for internal movement '.$movement->code,
                    meta: ['internal_movement_code' => $movement->code, 'reversal' => true],
                );
            }

            $movement->update([
                'status' => InternalMovementStatus::Reversed,
                'notes' => $notes ?: $movement->notes,
            ]);

            InternalMovementHistory::create([
                'internal_movement_id' => $movement->id,
                'from_status' => InternalMovementStatus::Completed->value,
                'to_status' => InternalMovementStatus::Reversed->value,
                'acted_by' => $actor->id,
                'reason' => 'Controlled reversal',
            ]);

            $this->auditLogService->record(
                $actor,
                'internal_movement',
                'internal_movement.reversed',
                'Reversed internal movement '.$movement->code,
                $movement,
                $movement->location,
                ['code' => $movement->code],
            );

            return $movement->fresh(['items.productVariant.product', 'location', 'requester']);
        });
    }
}
