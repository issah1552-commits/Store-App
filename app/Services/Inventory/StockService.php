<?php

namespace App\Services\Inventory;

use App\Enums\StockBucket;
use App\Enums\StockMovementType;
use App\Models\Location;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function availableQuantity(Location $location, ProductVariant $variant, StockBucket $bucket): int
    {
        return (int) Stock::query()
            ->where('location_id', $location->id)
            ->where('product_variant_id', $variant->id)
            ->where('bucket', $bucket->value)
            ->value('quantity');
    }

    public function addStock(
        User $actor,
        ProductVariant $variant,
        Location $destinationLocation,
        StockBucket $destinationBucket,
        int $quantity,
        StockMovementType $movementType,
        Model $reference,
        ?string $notes = null,
        array $meta = [],
    ): StockMovement {
        $this->guardPositiveQuantity($quantity);

        $balances = $this->lockBalances([
            'destination' => [$destinationLocation, $variant, $destinationBucket],
        ], $actor);

        $destination = $balances['destination'];
        $before = $destination->quantity;
        $destination->quantity += $quantity;
        $destination->updated_by = $actor->id;
        $destination->save();

        return StockMovement::create([
            'movement_type' => $movementType->value,
            'product_variant_id' => $variant->id,
            'destination_location_id' => $destinationLocation->id,
            'destination_bucket' => $destinationBucket->value,
            'destination_quantity_before' => $before,
            'destination_quantity_after' => $destination->quantity,
            'quantity' => $quantity,
            'reference_type' => $reference::class,
            'reference_id' => $reference->getKey(),
            'notes' => $notes,
            'meta' => $meta,
            'performed_by' => $actor->id,
            'occurred_at' => now(),
        ]);
    }

    public function deductStock(
        User $actor,
        ProductVariant $variant,
        Location $sourceLocation,
        StockBucket $sourceBucket,
        int $quantity,
        StockMovementType $movementType,
        Model $reference,
        ?string $notes = null,
        array $meta = [],
    ): StockMovement {
        $this->guardPositiveQuantity($quantity);

        $balances = $this->lockBalances([
            'source' => [$sourceLocation, $variant, $sourceBucket],
        ], $actor);

        $source = $balances['source'];
        $this->assertAvailable($source, $quantity, 'Insufficient stock for the requested operation.');

        $before = $source->quantity;
        $source->quantity -= $quantity;
        $source->updated_by = $actor->id;
        $source->save();

        return StockMovement::create([
            'movement_type' => $movementType->value,
            'product_variant_id' => $variant->id,
            'source_location_id' => $sourceLocation->id,
            'source_bucket' => $sourceBucket->value,
            'source_quantity_before' => $before,
            'source_quantity_after' => $source->quantity,
            'quantity' => $quantity,
            'reference_type' => $reference::class,
            'reference_id' => $reference->getKey(),
            'notes' => $notes,
            'meta' => $meta,
            'performed_by' => $actor->id,
            'occurred_at' => now(),
        ]);
    }

    public function moveStock(
        User $actor,
        ProductVariant $variant,
        Location $sourceLocation,
        StockBucket $sourceBucket,
        Location $destinationLocation,
        StockBucket $destinationBucket,
        int $quantity,
        StockMovementType $movementType,
        Model $reference,
        ?string $notes = null,
        array $meta = [],
    ): StockMovement {
        $this->guardPositiveQuantity($quantity);

        $balances = $this->lockBalances([
            'source' => [$sourceLocation, $variant, $sourceBucket],
            'destination' => [$destinationLocation, $variant, $destinationBucket],
        ], $actor);

        $source = $balances['source'];
        $destination = $balances['destination'];

        $this->assertAvailable($source, $quantity, 'Insufficient stock at source location.');

        $sourceBefore = $source->quantity;
        $destinationBefore = $destination->quantity;

        $source->quantity -= $quantity;
        $source->updated_by = $actor->id;
        $source->save();

        $destination->quantity += $quantity;
        $destination->updated_by = $actor->id;
        $destination->save();

        return StockMovement::create([
            'movement_type' => $movementType->value,
            'product_variant_id' => $variant->id,
            'source_location_id' => $sourceLocation->id,
            'source_bucket' => $sourceBucket->value,
            'source_quantity_before' => $sourceBefore,
            'source_quantity_after' => $source->quantity,
            'destination_location_id' => $destinationLocation->id,
            'destination_bucket' => $destinationBucket->value,
            'destination_quantity_before' => $destinationBefore,
            'destination_quantity_after' => $destination->quantity,
            'quantity' => $quantity,
            'reference_type' => $reference::class,
            'reference_id' => $reference->getKey(),
            'notes' => $notes,
            'meta' => $meta,
            'performed_by' => $actor->id,
            'occurred_at' => now(),
        ]);
    }

    protected function lockBalances(array $definitions, User $actor): Collection
    {
        $normalized = collect($definitions)
            ->map(fn (array $value, string $key) => [
                'key' => $key,
                'location' => $value[0],
                'variant' => $value[1],
                'bucket' => $value[2],
                'sort' => sprintf('%010d-%010d-%s', $value[0]->id, $value[1]->id, $value[2]->value),
            ])
            ->sortBy('sort')
            ->values();

        $locked = collect();

        foreach ($normalized as $entry) {
            $stock = Stock::query()
                ->where('location_id', $entry['location']->id)
                ->where('product_variant_id', $entry['variant']->id)
                ->where('bucket', $entry['bucket']->value)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = Stock::create([
                    'location_id' => $entry['location']->id,
                    'product_variant_id' => $entry['variant']->id,
                    'bucket' => $entry['bucket']->value,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'updated_by' => $actor->id,
                ]);

                $stock = Stock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
            }

            $locked->put($entry['key'], $stock);
        }

        return $locked;
    }

    protected function assertAvailable(Stock $stock, int $quantity, string $message): void
    {
        if ($stock->quantity < $quantity) {
            throw ValidationException::withMessages([
                'stock' => $message,
            ]);
        }
    }

    protected function guardPositiveQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be greater than zero.',
            ]);
        }
    }
}
