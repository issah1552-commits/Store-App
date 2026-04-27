<?php

namespace Tests\Feature\Inventory;

use App\Enums\StockBucket;
use App\Enums\TransferStatus;
use App\Models\Location;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_transfer_moves_from_warehouse_to_shop_wholesale_after_receipt_confirmation()
    {
        $warehouseUser = User::query()->where('email', 'warehouse.user@inventory.tz')->firstOrFail();
        $admin = User::query()->where('email', 'admin@inventory.tz')->firstOrFail();
        $shopManager = User::query()->where('email', 'dar.manager@inventory.tz')->firstOrFail();

        $warehouse = Location::query()->where('code', 'WAREHOUSE-DOD')->firstOrFail();
        $shop = Location::query()->where('code', 'SHOP-DAR')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'KILIMANJARO-PRINT-DELUXE-ROYAL-BLUE-50')->firstOrFail();

        $warehouseBefore = $this->stockQuantity($warehouse->id, $variant->id, StockBucket::Warehouse->value);
        $shopWholesaleBefore = $this->stockQuantity($shop->id, $variant->id, StockBucket::Wholesale->value);

        $createResponse = $this->actingAs($warehouseUser)->post(route('transfers.store'), [
            'source_location_id' => $warehouse->id,
            'destination_location_id' => $shop->id,
            'notes' => 'Planned replenishment for Dar es Salaam',
            'items' => [
                [
                    'product_variant_id' => $variant->id,
                    'requested_quantity' => 5,
                ],
            ],
        ]);

        $createResponse->assertRedirect();

        $transfer = Transfer::query()->latest('id')->firstOrFail();

        $this->assertSame(TransferStatus::PendingApproval, $transfer->status);

        $this->actingAs($admin)
            ->post(route('transfers.approve', $transfer))
            ->assertRedirect();

        $transfer->refresh();
        $this->assertSame(TransferStatus::Approved, $transfer->status);

        $this->actingAs($warehouseUser)
            ->post(route('transfers.dispatch', $transfer), [
                'notes' => 'Dispatched on morning truck',
                'items' => [
                    [
                        'product_variant_id' => $variant->id,
                        'quantity' => 5,
                    ],
                ],
            ])
            ->assertRedirect();

        $transfer->refresh();
        $this->assertSame(TransferStatus::Dispatched, $transfer->status);
        $this->assertSame($warehouseBefore - 5, $this->stockQuantity($warehouse->id, $variant->id, StockBucket::Warehouse->value));
        $this->assertSame(5, $this->stockQuantity($shop->id, $variant->id, StockBucket::InTransit->value));

        $this->actingAs($shopManager)
            ->post(route('transfers.receive', $transfer), [
                'notes' => 'Received in full at Dar store',
                'items' => [
                    [
                        'product_variant_id' => $variant->id,
                        'quantity' => 5,
                    ],
                ],
            ])
            ->assertRedirect();

        $transfer->refresh();

        $this->assertSame(TransferStatus::Received, $transfer->status);
        $this->assertSame(0, $this->stockQuantity($shop->id, $variant->id, StockBucket::InTransit->value));
        $this->assertSame($shopWholesaleBefore + 5, $this->stockQuantity($shop->id, $variant->id, StockBucket::Wholesale->value));
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
