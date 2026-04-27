<?php

namespace Database\Seeders;

use App\Enums\StockBucket;
use App\Enums\StockMovementType;
use App\Enums\TransferStatus;
use App\Models\Location;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\TransferStatusHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransferSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Location::query()->where('code', 'WAREHOUSE-DOD')->firstOrFail();
        $dar = Location::query()->where('code', 'SHOP-DAR')->firstOrFail();
        $arusha = Location::query()->where('code', 'SHOP-ARU')->firstOrFail();
        $admin = User::query()->where('email', 'admin@inventory.tz')->firstOrFail();
        $warehouseManager = User::query()->where('email', 'warehouse.manager@inventory.tz')->firstOrFail();
        $darManager = User::query()->where('email', 'dar.manager@inventory.tz')->firstOrFail();
        $arushaUser = User::query()->where('email', 'arusha.ops@inventory.tz')->firstOrFail();

        $pending = Transfer::updateOrCreate(
            ['code' => 'TRF-202604-001'],
            [
                'source_location_id' => $warehouse->id,
                'destination_location_id' => $dar->id,
                'status' => TransferStatus::PendingApproval->value,
                'requested_by' => $warehouseManager->id,
                'notes' => 'Routine replenishment for Dar es Salaam.',
            ],
        );

        $pendingVariant = ProductVariant::query()->where('sku', 'KILIMANJARO-PRINT-DELUXE-ROYAL-BLUE-50')->firstOrFail();

        TransferItem::updateOrCreate(
            ['transfer_id' => $pending->id, 'product_variant_id' => $pendingVariant->id],
            ['requested_quantity' => 12, 'approved_quantity' => null, 'dispatched_quantity' => 0, 'received_quantity' => 0, 'variance_quantity' => 0],
        );

        TransferStatusHistory::firstOrCreate(
            ['transfer_id' => $pending->id, 'to_status' => TransferStatus::PendingApproval->value, 'acted_by' => $warehouseManager->id],
            ['from_status' => TransferStatus::Draft->value, 'reason' => 'Submitted for approval'],
        );

        $dispatched = Transfer::updateOrCreate(
            ['code' => 'TRF-202604-002'],
            [
                'source_location_id' => $warehouse->id,
                'destination_location_id' => $arusha->id,
                'status' => TransferStatus::Dispatched->value,
                'requested_by' => $warehouseManager->id,
                'approved_by' => $admin->id,
                'dispatched_by' => $warehouseManager->id,
                'approved_at' => now()->subDays(3),
                'dispatched_at' => now()->subDays(2),
                'notes' => 'Awaiting Arusha receipt confirmation.',
            ],
        );

        $dispatchVariant = ProductVariant::query()->where('sku', 'SERENGETI-CANVAS-PRO-OCEAN-BLUE-30')->firstOrFail();

        TransferItem::updateOrCreate(
            ['transfer_id' => $dispatched->id, 'product_variant_id' => $dispatchVariant->id],
            ['requested_quantity' => 6, 'approved_quantity' => 6, 'dispatched_quantity' => 6, 'received_quantity' => 0, 'variance_quantity' => 0],
        );

        TransferStatusHistory::firstOrCreate(
            ['transfer_id' => $dispatched->id, 'to_status' => TransferStatus::Approved->value, 'acted_by' => $admin->id],
            ['from_status' => TransferStatus::PendingApproval->value, 'reason' => 'Approved by admin'],
        );
        TransferStatusHistory::firstOrCreate(
            ['transfer_id' => $dispatched->id, 'to_status' => TransferStatus::Dispatched->value, 'acted_by' => $warehouseManager->id],
            ['from_status' => TransferStatus::Approved->value, 'reason' => 'Dispatched to Arusha'],
        );

        StockMovement::firstOrCreate(
            [
                'movement_type' => StockMovementType::TransferDispatch->value,
                'product_variant_id' => $dispatchVariant->id,
                'reference_type' => 'transfer',
                'reference_id' => $dispatched->id,
                'source_location_id' => $warehouse->id,
                'source_bucket' => StockBucket::Warehouse->value,
                'destination_location_id' => $arusha->id,
                'destination_bucket' => StockBucket::InTransit->value,
                'quantity' => 6,
            ],
            [
                'source_quantity_before' => 66,
                'source_quantity_after' => 60,
                'destination_quantity_before' => 0,
                'destination_quantity_after' => 6,
                'notes' => 'Warehouse dispatch to Arusha in transit',
                'performed_by' => $warehouseManager->id,
                'occurred_at' => now()->subDays(2),
            ],
        );

        $received = Transfer::updateOrCreate(
            ['code' => 'TRF-202604-003'],
            [
                'source_location_id' => $warehouse->id,
                'destination_location_id' => $dar->id,
                'status' => TransferStatus::Closed->value,
                'requested_by' => $warehouseManager->id,
                'approved_by' => $admin->id,
                'dispatched_by' => $warehouseManager->id,
                'received_by' => $darManager->id,
                'closed_by' => $admin->id,
                'approved_at' => now()->subDays(8),
                'dispatched_at' => now()->subDays(7),
                'received_at' => now()->subDays(6),
                'closed_at' => now()->subDays(5),
                'notes' => 'Completed transfer to Dar es Salaam.',
            ],
        );

        $receivedVariant = ProductVariant::query()->where('sku', 'KILIMANJARO-PRINT-DELUXE-CRIMSON-RED-50')->firstOrFail();

        TransferItem::updateOrCreate(
            ['transfer_id' => $received->id, 'product_variant_id' => $receivedVariant->id],
            ['requested_quantity' => 8, 'approved_quantity' => 8, 'dispatched_quantity' => 8, 'received_quantity' => 8, 'variance_quantity' => 0],
        );

        foreach ([
            [TransferStatus::Approved->value, $admin->id, TransferStatus::PendingApproval->value, 'Approved by admin'],
            [TransferStatus::Dispatched->value, $warehouseManager->id, TransferStatus::Approved->value, 'Dispatched from Dodoma warehouse'],
            [TransferStatus::Received->value, $darManager->id, TransferStatus::Dispatched->value, 'Received by Dar es Salaam'],
            [TransferStatus::Closed->value, $admin->id, TransferStatus::Received->value, 'Closed with no variance'],
        ] as [$toStatus, $actorId, $fromStatus, $reason]) {
            TransferStatusHistory::firstOrCreate(
                ['transfer_id' => $received->id, 'to_status' => $toStatus, 'acted_by' => $actorId],
                ['from_status' => $fromStatus, 'reason' => $reason],
            );
        }

        $variance = Transfer::updateOrCreate(
            ['code' => 'TRF-202604-004'],
            [
                'source_location_id' => $warehouse->id,
                'destination_location_id' => $arusha->id,
                'status' => TransferStatus::ClosedWithVariance->value,
                'requested_by' => $warehouseManager->id,
                'approved_by' => $admin->id,
                'dispatched_by' => $warehouseManager->id,
                'received_by' => $arushaUser->id,
                'closed_by' => $admin->id,
                'approved_at' => now()->subDays(4),
                'dispatched_at' => now()->subDays(3),
                'received_at' => now()->subDays(2),
                'closed_at' => now()->subDay(),
                'has_variance' => true,
                'notes' => 'Short receipt recorded due to damaged rolls.',
            ],
        );

        $varianceVariant = ProductVariant::query()->where('sku', 'SAVANNA-SHADE-SHIELD-FOREST-GREEN-100')->firstOrFail();

        TransferItem::updateOrCreate(
            ['transfer_id' => $variance->id, 'product_variant_id' => $varianceVariant->id],
            ['requested_quantity' => 4, 'approved_quantity' => 4, 'dispatched_quantity' => 4, 'received_quantity' => 3, 'variance_quantity' => 1],
        );
    }
}
