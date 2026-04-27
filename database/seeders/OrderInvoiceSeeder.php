<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $dar = Location::query()->where('code', 'SHOP-DAR')->firstOrFail();
        $mwanza = Location::query()->where('code', 'SHOP-MWA')->firstOrFail();
        $darManager = User::query()->where('email', 'dar.manager@inventory.tz')->firstOrFail();
        $retailStaff = User::query()->where('email', 'mwanza.retail@inventory.tz')->firstOrFail();

        $darVariant = ProductVariant::query()->where('sku', 'KILIMANJARO-PRINT-DELUXE-ROYAL-BLUE-50')->firstOrFail();
        $mwanzaVariant = ProductVariant::query()->where('sku', 'SERENGETI-CANVAS-PRO-OCEAN-BLUE-30')->firstOrFail();

        $completedOrder = Order::updateOrCreate(
            ['order_number' => 'ORD-DAR-0001'],
            [
                'location_id' => $dar->id,
                'ordered_by' => $darManager->id,
                'status' => OrderStatus::Completed->value,
                'customer_name' => 'Mzee Kondo Traders',
                'customer_phone' => '+255700111222',
                'subtotal_tzs' => 960000,
                'discount_tzs' => 20000,
                'total_tzs' => 940000,
                'completed_at' => now()->subDays(2),
                'notes' => 'Two-roll wholesale desk sale from Dar retail stock.',
            ],
        );

        OrderItem::updateOrCreate(
            ['order_id' => $completedOrder->id, 'product_variant_id' => $darVariant->id],
            ['quantity' => 2, 'unit_price_tzs' => 480000, 'line_total_tzs' => 960000],
        );

        $invoice = Invoice::updateOrCreate(
            ['invoice_number' => 'INV-DAR-0001'],
            [
                'order_id' => $completedOrder->id,
                'location_id' => $dar->id,
                'issued_by' => $darManager->id,
                'status' => InvoiceStatus::Issued->value,
                'payment_status' => PaymentStatus::Paid->value,
                'subtotal_tzs' => 960000,
                'tax_tzs' => 0,
                'discount_tzs' => 20000,
                'total_tzs' => 940000,
                'amount_paid_tzs' => 940000,
                'balance_tzs' => 0,
                'issued_at' => now()->subDays(2),
                'paid_at' => now()->subDays(2),
                'notes' => 'TZS cash sale.',
            ],
        );

        InvoiceItem::updateOrCreate(
            ['invoice_id' => $invoice->id, 'description' => $darVariant->product->brand_name.' / '.$darVariant->color],
            ['product_variant_id' => $darVariant->id, 'quantity' => 2, 'unit_price_tzs' => 480000, 'line_total_tzs' => 960000],
        );

        // Create 4 more completed orders for Dar to reach at least 5 orders
        for ($i = 2; $i <= 5; $i++) {
            $orderNumber = 'ORD-DAR-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $invoiceNumber = 'INV-DAR-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            
            $additionalOrder = Order::updateOrCreate(
                ['order_number' => $orderNumber],
                [
                    'location_id' => $dar->id,
                    'ordered_by' => $darManager->id,
                    'status' => OrderStatus::Completed->value,
                    'customer_name' => 'Additional Customer ' . $i,
                    'customer_phone' => '+25570011122' . $i,
                    'subtotal_tzs' => 480000,
                    'discount_tzs' => 0,
                    'total_tzs' => 480000,
                    'completed_at' => now()->subDays(1),
                    'notes' => "Additional bulk order {$i} from Dar retail stock.",
                ],
            );

            OrderItem::updateOrCreate(
                ['order_id' => $additionalOrder->id, 'product_variant_id' => $darVariant->id],
                ['quantity' => 1, 'unit_price_tzs' => 480000, 'line_total_tzs' => 480000],
            );

            $additionalInvoice = Invoice::updateOrCreate(
                ['invoice_number' => $invoiceNumber],
                [
                    'order_id' => $additionalOrder->id,
                    'location_id' => $dar->id,
                    'issued_by' => $darManager->id,
                    'status' => InvoiceStatus::Issued->value,
                    'payment_status' => PaymentStatus::Paid->value,
                    'subtotal_tzs' => 480000,
                    'tax_tzs' => 0,
                    'discount_tzs' => 0,
                    'total_tzs' => 480000,
                    'amount_paid_tzs' => 480000,
                    'balance_tzs' => 0,
                    'issued_at' => now()->subDays(1),
                    'paid_at' => now()->subDays(1),
                    'notes' => 'TZS cash sale.',
                ],
            );

            InvoiceItem::updateOrCreate(
                ['invoice_id' => $additionalInvoice->id, 'description' => $darVariant->product->brand_name.' / '.$darVariant->color],
                ['product_variant_id' => $darVariant->id, 'quantity' => 1, 'unit_price_tzs' => 480000, 'line_total_tzs' => 480000],
            );
        }

        $pendingOrder = Order::updateOrCreate(
            ['order_number' => 'ORD-MWA-0001'],
            [
                'location_id' => $mwanza->id,
                'ordered_by' => $retailStaff->id,
                'status' => OrderStatus::Pending->value,
                'customer_name' => 'Lake Zone Supplies',
                'customer_phone' => '+255700333444',
                'subtotal_tzs' => 520000,
                'discount_tzs' => 0,
                'total_tzs' => 520000,
                'notes' => 'Pending confirmation before stock posting.',
            ],
        );

        OrderItem::updateOrCreate(
            ['order_id' => $pendingOrder->id, 'product_variant_id' => $mwanzaVariant->id],
            ['quantity' => 1, 'unit_price_tzs' => 520000, 'line_total_tzs' => 520000],
        );
    }
}
