<?php

namespace App\Actions\Orders;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateInvoiceAction
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function __invoke(User $actor, array $payload): Invoice
    {
        $order = isset($payload['order_id']) ? Order::query()->with('items.productVariant.product', 'location')->findOrFail($payload['order_id']) : null;

        if (! $order && empty($payload['items'])) {
            throw ValidationException::withMessages([
                'items' => 'Manual invoices require at least one item.',
            ]);
        }

        if ($order?->invoice()->exists()) {
            throw ValidationException::withMessages([
                'order_id' => 'The selected order already has an invoice.',
            ]);
        }

        return DB::transaction(function () use ($actor, $payload, $order) {
            $location = $order?->location ?? $actor->defaultLocation;
            $itemsPayload = $order
                ? $order->items->map(fn ($item) => [
                    'product_variant_id' => $item->product_variant_id,
                    'description' => $item->productVariant->product->brand_name.' / '.$item->productVariant->color,
                    'quantity' => $item->quantity,
                    'unit_price_tzs' => $item->unit_price_tzs,
                    'line_total_tzs' => $item->line_total_tzs,
                ])->all()
                : $payload['items'];

            $invoice = Invoice::create([
                'invoice_number' => 'INV-'.str($location->code)->after('SHOP-')->upper()->value().'-'.Str::upper(Str::random(6)),
                'order_id' => $order?->id,
                'location_id' => $location->id,
                'issued_by' => $actor->id,
                'status' => InvoiceStatus::Issued,
                'payment_status' => PaymentStatus::Unpaid,
                'discount_tzs' => $payload['discount_tzs'] ?? 0,
                'tax_tzs' => $payload['tax_tzs'] ?? 0,
                'notes' => $payload['notes'] ?? null,
                'issued_at' => now(),
            ]);

            $subtotal = 0;

            foreach ($itemsPayload as $item) {
                $variant = isset($item['product_variant_id']) ? ProductVariant::query()->find($item['product_variant_id']) : null;
                $lineTotal = (float) ($item['line_total_tzs'] ?? ((int) $item['quantity'] * (float) $item['unit_price_tzs']));
                $subtotal += $lineTotal;

                $invoice->items()->create([
                    'product_variant_id' => $variant?->id,
                    'description' => $item['description'] ?? ($variant ? $variant->product->brand_name.' / '.$variant->color : 'Manual line item'),
                    'quantity' => (int) $item['quantity'],
                    'unit_price_tzs' => $item['unit_price_tzs'],
                    'line_total_tzs' => $lineTotal,
                ]);
            }

            $total = max($subtotal + (float) ($payload['tax_tzs'] ?? 0) - (float) ($payload['discount_tzs'] ?? 0), 0);

            $invoice->update([
                'subtotal_tzs' => $subtotal,
                'total_tzs' => $total,
                'balance_tzs' => $total,
            ]);

            $this->auditLogService->record(
                $actor,
                'invoice',
                'invoice.created',
                'Created invoice '.$invoice->invoice_number,
                $invoice,
                $location,
                ['invoice_number' => $invoice->invoice_number],
            );

            return $invoice->fresh(['items.productVariant.product', 'location', 'issuer', 'order']);
        });
    }
}
