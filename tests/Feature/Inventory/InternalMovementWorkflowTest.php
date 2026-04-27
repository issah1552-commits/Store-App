<?php

namespace Tests\Feature\Inventory;

use App\Enums\InternalMovementStatus;
use App\Enums\StockBucket;
use App\Models\InternalMovement;
use App\Models\Location;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalMovementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_in_limit_internal_movement_completes_and_moves_stock()
    {
        $shopUser = User::query()->where('email', 'arusha.ops@inventory.tz')->firstOrFail();
        $location = Location::query()->where('code', 'SHOP-ARU')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'KILIMANJARO-PRINT-DELUXE-ROYAL-BLUE-50')->firstOrFail();

        $wholesaleBefore = $this->stockQuantity($location->id, $variant->id, StockBucket::Wholesale->value);
        $retailBefore = $this->stockQuantity($location->id, $variant->id, StockBucket::Retail->value);

        $response = $this->actingAs($shopUser)->post(route('internal-movements.store'), [
            'location_id' => $location->id,
            'notes' => 'Restocking retail display shelves',
            'items' => [
                [
                    'product_variant_id' => $variant->id,
                    'quantity' => 4,
                ],
            ],
        ]);

        $response->assertRedirect();

        $movement = InternalMovement::query()->latest('id')->firstOrFail();

        $this->assertSame(InternalMovementStatus::Completed, $movement->status);
        $this->assertSame($wholesaleBefore - 4, $this->stockQuantity($location->id, $variant->id, StockBucket::Wholesale->value));
        $this->assertSame($retailBefore + 4, $this->stockQuantity($location->id, $variant->id, StockBucket::Retail->value));
    }

    public function test_over_limit_internal_movement_escalates_until_approved()
    {
        $shopManager = User::query()->where('email', 'dar.manager@inventory.tz')->firstOrFail();
        $location = Location::query()->where('code', 'SHOP-DAR')->firstOrFail();

        $royalBlue = ProductVariant::query()->where('sku', 'KILIMANJARO-PRINT-DELUXE-ROYAL-BLUE-50')->firstOrFail();
        $crimsonRed = ProductVariant::query()->where('sku', 'KILIMANJARO-PRINT-DELUXE-CRIMSON-RED-50')->firstOrFail();
        $emerald = ProductVariant::query()->where('sku', 'KILIMANJARO-PRINT-DELUXE-EMERALD-GREEN-70')->firstOrFail();
        $oceanBlue = ProductVariant::query()->where('sku', 'SERENGETI-CANVAS-PRO-OCEAN-BLUE-30')->firstOrFail();

        $royalBlueWholesaleBefore = $this->stockQuantity($location->id, $royalBlue->id, StockBucket::Wholesale->value);
        $royalBlueRetailBefore = $this->stockQuantity($location->id, $royalBlue->id, StockBucket::Retail->value);

        $createResponse = $this->actingAs($shopManager)->post(route('internal-movements.store'), [
            'location_id' => $location->id,
            'notes' => 'Large floor refresh before weekend sales push',
            'items' => [
                ['product_variant_id' => $royalBlue->id, 'quantity' => 18],
                ['product_variant_id' => $crimsonRed->id, 'quantity' => 10],
                ['product_variant_id' => $emerald->id, 'quantity' => 7],
                ['product_variant_id' => $oceanBlue->id, 'quantity' => 6],
            ],
        ]);

        $createResponse->assertRedirect();

        $movement = InternalMovement::query()->latest('id')->firstOrFail();

        $this->assertSame(InternalMovementStatus::Escalated, $movement->status);
        $this->assertSame($royalBlueWholesaleBefore, $this->stockQuantity($location->id, $royalBlue->id, StockBucket::Wholesale->value));
        $this->assertSame($royalBlueRetailBefore, $this->stockQuantity($location->id, $royalBlue->id, StockBucket::Retail->value));

        $approveResponse = $this->actingAs($shopManager)->post(route('internal-movements.approve', $movement), [
            'notes' => 'Approved due to scheduled promotion demand',
        ]);

        $approveResponse->assertRedirect();

        $movement->refresh();

        $this->assertSame(InternalMovementStatus::Completed, $movement->status);
        $this->assertSame($royalBlueWholesaleBefore - 18, $this->stockQuantity($location->id, $royalBlue->id, StockBucket::Wholesale->value));
        $this->assertSame($royalBlueRetailBefore + 18, $this->stockQuantity($location->id, $royalBlue->id, StockBucket::Retail->value));
    }

    protected function stockQuantity(int $locationId, int $variantId, string $bucket): int
    {
        return (int) Stock::query()
            ->where('location_id', $locationId)
            ->where('product_variant_id', $variantId)
            ->where('bucket', $bucket)
            ->value('quantity');
    }
}
