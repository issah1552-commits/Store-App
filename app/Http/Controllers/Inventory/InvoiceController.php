<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Orders\CreateInvoiceAction;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\InvoiceStoreRequest;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\AuditLogService;
use App\Services\LocationContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request, LocationContextService $locationContext): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $user = $request->user();
        $selectedLocation = $locationContext->resolveSelectedLocation($request, $user);
        $locationIds = $locationContext->scopedLocationIds($request, $user);

        $invoices = Invoice::query()
            ->with(['location:id,name', 'issuer:id,name', 'order:id,order_number'])
            ->whereIn('location_id', $locationIds)
            ->latest('issued_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('invoices/index', [
            'invoices' => $invoices,
            'canCreate' => $request->user()->can('create', Invoice::class) && $selectedLocation?->type !== \App\Enums\LocationType::Warehouse,
        ]);
    }

    public function create(Request $request, LocationContextService $locationContext): Response
    {
        $this->authorize('create', Invoice::class);

        $selectedLocation = $locationContext->resolveSelectedLocation($request, $request->user());
        abort_if($selectedLocation?->type === \App\Enums\LocationType::Warehouse, 403);

        $locationIds = $locationContext->scopedLocationIds($request, $request->user());

        return Inertia::render('invoices/form', [
            'orders' => Order::query()
                ->whereIn('location_id', $locationIds)
                ->where('status', \App\Enums\OrderStatus::Completed)
                ->whereDoesntHave('invoice')
                ->with('location:id,name')
                ->latest()
                ->get(['id', 'order_number', 'location_id', 'total_tzs', 'completed_at']),
        ]);
    }

    public function store(InvoiceStoreRequest $request, CreateInvoiceAction $action, LocationContextService $locationContext): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $selectedLocation = $locationContext->resolveSelectedLocation($request, $request->user());

        if ($selectedLocation) {
            $order = Order::query()->findOrFail($request->integer('order_id'));
            abort_unless((int) $selectedLocation->getKey() === (int) $order->location_id, 403);
        }

        $invoice = $action($request->user(), $request->validated());

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function show(Invoice $invoice, Request $request): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['location:id,name,code', 'issuer:id,name', 'order:id,order_number', 'items.productVariant.product']);

        return Inertia::render('invoices/show', [
            'invoice' => $invoice,
            'canMarkPaid' => $request->user()->can('markPaid', $invoice),
            'canVoid' => $request->user()->can('void', $invoice),
        ]);
    }

    public function markPaid(Invoice $invoice, Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorize('markPaid', $invoice);

        $invoice->update([
            'payment_status' => PaymentStatus::Paid,
            'amount_paid_tzs' => $invoice->total_tzs,
            'balance_tzs' => 0,
            'paid_at' => now(),
        ]);

        $auditLogService->record(
            $request->user(),
            'invoice',
            'invoice.mark_paid',
            'Marked invoice '.$invoice->invoice_number.' as paid',
            $invoice,
            $invoice->location,
            ['invoice_number' => $invoice->invoice_number],
            $request,
        );

        return back()->with('success', 'Invoice marked as paid.');
    }

    public function void(Invoice $invoice, Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorize('void', $invoice);

        $invoice->update([
            'status' => InvoiceStatus::Void,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $auditLogService->record(
            $request->user(),
            'invoice',
            'invoice.voided',
            'Voided invoice '.$invoice->invoice_number,
            $invoice,
            $invoice->location,
            ['invoice_number' => $invoice->invoice_number],
            $request,
        );

        return back()->with('success', 'Invoice voided.');
    }

    public function print(Invoice $invoice, Request $request): Response
    {
        $this->authorize('view', $invoice);
        $invoice->load(['location:id,name,code', 'issuer:id,name', 'order:id,order_number', 'items.productVariant.product']);

        return Inertia::render('invoices/print', [
            'invoice' => $invoice,
        ]);
    }
}
