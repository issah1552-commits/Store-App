<?php

namespace App\Actions\InternalMovements;

use App\Enums\InternalMovementStatus;
use App\Enums\StockBucket;
use App\Enums\StockMovementType;
use App\Models\InternalMovement;
use App\Models\InternalMovementHistory;
use App\Models\InternalMovementRule;
use App\Models\Location;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateInternalMovementAction
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function __invoke(User $actor, Location $location, array $payload): InternalMovement
    {
        $items = collect($payload['items'] ?? []);

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'At least one internal movement item is required.',
            ]);
        }

        $variants = ProductVariant::query()->with('product.category')->whereIn('id', $items->pluck('product_variant_id'))->get()->keyBy('id');

        $rule = $this->resolveRule($actor, $location, $variants->values());
        $notes = trim((string) ($payload['notes'] ?? ''));
        $totalQuantity = 0;
        $totalValue = 0.0;
        $shouldEscalate = false;

        if ($rule->requires_reason && $notes === '') {
            throw ValidationException::withMessages([
                'notes' => 'A reason is required for internal stock movement requests.',
            ]);
        }

        foreach ($items as $item) {
            $variant = $variants->get($item['product_variant_id']);
            $quantity = (int) $item['quantity'];

            if (! $variant || $quantity <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Invalid internal movement item payload.',
                ]);
            }

            $totalQuantity += $quantity;
            $totalValue += $quantity * (float) $variant->retail_price_tzs;

            if (($rule->max_quantity_per_item && $quantity > $rule->max_quantity_per_item)
                || ($rule->max_quantity_per_transaction && $totalQuantity > $rule->max_quantity_per_transaction)) {
                $shouldEscalate = true;
            }
        }

        if ($rule->max_daily_quantity_per_user) {
            $currentDayQuantity = InternalMovement::query()
                ->where('location_id', $location->id)
                ->where('requested_by', $actor->id)
                ->whereDate('created_at', today())
                ->whereIn('status', [
                    InternalMovementStatus::Escalated,
                    InternalMovementStatus::Approved,
                    InternalMovementStatus::Completed,
                ])
                ->sum('total_quantity');

            if (($currentDayQuantity + $totalQuantity) > $rule->max_daily_quantity_per_user) {
                $shouldEscalate = true;
            }
        }

        if ($rule->after_hours_blocked && $this->isAfterHoursBlocked($rule)) {
            $shouldEscalate = true;
        }

        $movement = DB::transaction(function () use ($actor, $location, $items, $variants, $totalQuantity, $totalValue, $shouldEscalate, $notes) {
            $movement = InternalMovement::create([
                'code' => 'IM-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'location_id' => $location->id,
                'status' => $shouldEscalate ? InternalMovementStatus::Escalated : InternalMovementStatus::Completed,
                'requested_by' => $actor->id,
                'completed_by' => $shouldEscalate ? null : $actor->id,
                'completed_at' => $shouldEscalate ? null : now(),
                'total_quantity' => $totalQuantity,
                'total_value_tzs' => $totalValue,
                'escalation_reason' => $shouldEscalate ? 'Movement exceeded automatic approval thresholds.' : null,
                'notes' => $notes !== '' ? $notes : null,
            ]);

            foreach ($items as $item) {
                $variant = $variants->get($item['product_variant_id']);
                $quantity = (int) $item['quantity'];

                $movement->items()->create([
                    'product_variant_id' => $variant->id,
                    'source_bucket' => StockBucket::Wholesale->value,
                    'destination_bucket' => StockBucket::Retail->value,
                    'quantity' => $quantity,
                    'unit_value_tzs' => $variant->retail_price_tzs,
                    'notes' => $item['notes'] ?? null,
                ]);

                if (! $shouldEscalate) {
                    $this->stockService->moveStock(
                        actor: $actor,
                        variant: $variant,
                        sourceLocation: $location,
                        sourceBucket: StockBucket::Wholesale,
                        destinationLocation: $location,
                        destinationBucket: StockBucket::Retail,
                        quantity: $quantity,
                        movementType: StockMovementType::InternalMovement,
                        reference: $movement,
                        notes: 'Automatic internal movement completion',
                        meta: ['internal_movement_code' => $movement->code],
                    );
                }
            }

            InternalMovementHistory::create([
                'internal_movement_id' => $movement->id,
                'from_status' => null,
                'to_status' => $movement->status->value,
                'acted_by' => $actor->id,
                'reason' => $shouldEscalate ? 'Escalated for approval' : 'Completed within thresholds',
            ]);

            $this->auditLogService->record(
                $actor,
                'internal_movement',
                $shouldEscalate ? 'internal_movement.escalated' : 'internal_movement.completed',
                'Created internal movement '.$movement->code,
                $movement,
                $location,
                ['code' => $movement->code, 'status' => $movement->status->value, 'total_quantity' => $totalQuantity],
            );

            return $movement;
        });

        return $movement->fresh(['items.productVariant.product', 'location', 'requester', 'completer']);
    }

    protected function resolveRule(User $actor, Location $location, Collection $variants): InternalMovementRule
    {
        $categoryIds = $variants->pluck('product.category_id')->filter()->unique();

        $rules = InternalMovementRule::query()
            ->where('is_active', true)
            ->where(function ($query) use ($location) {
                $query->whereNull('location_id')->orWhere('location_id', $location->id);
            })
            ->where(function ($query) use ($actor) {
                $query->whereNull('role_id')->orWhere('role_id', $actor->role_id);
            })
            ->where(function ($query) use ($categoryIds) {
                $query->whereNull('category_id')->orWhereIn('category_id', $categoryIds);
            })
            ->get();

        if ($rules->isEmpty()) {
            return new InternalMovementRule([
                'max_quantity_per_item' => 10,
                'max_quantity_per_transaction' => 20,
                'max_daily_quantity_per_user' => 30,
                'after_hours_blocked' => false,
                'requires_reason' => true,
                'is_active' => true,
            ]);
        }

        return $rules->reduce(function (?InternalMovementRule $carry, InternalMovementRule $rule) {
            if (! $carry) {
                return $rule;
            }

            $carry->max_quantity_per_item = $this->minimumNonNull($carry->max_quantity_per_item, $rule->max_quantity_per_item);
            $carry->max_quantity_per_transaction = $this->minimumNonNull($carry->max_quantity_per_transaction, $rule->max_quantity_per_transaction);
            $carry->max_daily_quantity_per_user = $this->minimumNonNull($carry->max_daily_quantity_per_user, $rule->max_daily_quantity_per_user);
            $carry->after_hours_blocked = $carry->after_hours_blocked || $rule->after_hours_blocked;
            $carry->requires_reason = $carry->requires_reason || $rule->requires_reason;

            return $carry;
        });
    }

    protected function minimumNonNull(?int $current, ?int $incoming): ?int
    {
        if ($current === null) {
            return $incoming;
        }

        if ($incoming === null) {
            return $current;
        }

        return min($current, $incoming);
    }

    protected function isAfterHoursBlocked(InternalMovementRule $rule): bool
    {
        if (! $rule->after_hours_start || ! $rule->after_hours_end) {
            return false;
        }

        $now = now()->format('H:i:s');

        return $rule->after_hours_start <= $rule->after_hours_end
            ? $now >= $rule->after_hours_start && $now <= $rule->after_hours_end
            : $now >= $rule->after_hours_start || $now <= $rule->after_hours_end;
    }
}
