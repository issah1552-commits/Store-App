<?php

namespace Database\Seeders;

use App\Enums\StockBucket;
use App\Enums\StockMovementType;
use App\Models\Location;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Location::query()->where('code', 'WAREHOUSE-DOD')->firstOrFail();
        $dar = Location::query()->where('code', 'SHOP-DAR')->firstOrFail();
        $arusha = Location::query()->where('code', 'SHOP-ARU')->firstOrFail();
        $mwanza = Location::query()->where('code', 'SHOP-MWA')->firstOrFail();
        $actor = User::query()->where('email', 'warehouse.manager@inventory.tz')->firstOrFail();

        $quantities = [
            'KILIMANJARO-PRINT-DELUXE-ROYAL-BLUE-50' => [
                [$warehouse->id, StockBucket::Warehouse, 12000],
                [$dar->id, StockBucket::Wholesale, 18],
                [$dar->id, StockBucket::Retail, 6],
                [$arusha->id, StockBucket::Wholesale, 9],
                [$arusha->id, StockBucket::Retail, 3],
                [$mwanza->id, StockBucket::Wholesale, 2],
                [$mwanza->id, StockBucket::Retail, 0],
            ],
            'KILIMANJARO-PRINT-DELUXE-CRIMSON-RED-50' => [
                [$warehouse->id, StockBucket::Warehouse, 9000],
                [$dar->id, StockBucket::Wholesale, 10],
                [$dar->id, StockBucket::Retail, 2],
                [$arusha->id, StockBucket::Wholesale, 4],
                [$arusha->id, StockBucket::Retail, 1],
                [$mwanza->id, StockBucket::Wholesale, 0],
                [$mwanza->id, StockBucket::Retail, 0],
            ],
            'KILIMANJARO-PRINT-DELUXE-EMERALD-GREEN-70' => [
                [$warehouse->id, StockBucket::Warehouse, 4400],
                [$dar->id, StockBucket::Wholesale, 7],
                [$dar->id, StockBucket::Retail, 2],
                [$arusha->id, StockBucket::Wholesale, 3],
                [$arusha->id, StockBucket::Retail, 0],
            ],
            'SERENGETI-CANVAS-PRO-OCEAN-BLUE-30' => [
                [$warehouse->id, StockBucket::Warehouse, 6000],
                [$dar->id, StockBucket::Wholesale, 12],
                [$dar->id, StockBucket::Retail, 4],
                [$mwanza->id, StockBucket::Wholesale, 6],
                [$mwanza->id, StockBucket::Retail, 2],
            ],
            'SERENGETI-CANVAS-PRO-ASH-GREY-45' => [
                [$warehouse->id, StockBucket::Warehouse, 2500],
                [$arusha->id, StockBucket::Wholesale, 2],
                [$arusha->id, StockBucket::Retail, 0],
            ],
            'SAVANNA-SHADE-SHIELD-FOREST-GREEN-100' => [
                [$warehouse->id, StockBucket::Warehouse, 1800],
                [$dar->id, StockBucket::Wholesale, 4],
                [$dar->id, StockBucket::Retail, 1],
            ],
            'SAVANNA-SHADE-SHIELD-BLACK-100' => [
                [$warehouse->id, StockBucket::Warehouse, 1000],
                [$mwanza->id, StockBucket::Wholesale, 1],
                [$mwanza->id, StockBucket::Retail, 0],
            ],
        ];

        foreach ($quantities as $sku => $rows) {
            $variant = ProductVariant::query()->where('sku', $sku)->firstOrFail();

            foreach ($rows as [$locationId, $bucket, $quantity]) {
                $stock = Stock::updateOrCreate(
                    [
                        'location_id' => $locationId,
                        'product_variant_id' => $variant->id,
                        'bucket' => $bucket->value,
                    ],
                    [
                        'quantity' => $quantity,
                        'reserved_quantity' => 0,
                        'updated_by' => $actor->id,
                    ],
                );

                StockMovement::firstOrCreate(
                    [
                        'movement_type' => StockMovementType::OpeningBalance->value,
                        'product_variant_id' => $variant->id,
                        'destination_location_id' => $locationId,
                        'destination_bucket' => $bucket->value,
                        'quantity' => $quantity,
                        'reference_type' => 'seeder_opening_stock',
                        'reference_id' => $stock->id,
                    ],
                    [
                        'destination_quantity_before' => 0,
                        'destination_quantity_after' => $quantity,
                        'notes' => 'Opening seeded stock',
                        'performed_by' => $actor->id,
                        'occurred_at' => now()->subDays(10),
                    ],
                );
            }
        }
    }
}
