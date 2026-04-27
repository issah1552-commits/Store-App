<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Transfers\ApproveTransferAction;
use App\Actions\Transfers\CloseTransferAction;
use App\Actions\Transfers\ConfirmTransferReceiptAction;
use App\Actions\Transfers\DispatchTransferAction;
use App\Enums\LocationType;
use App\Enums\TransferStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transfers\CloseTransferRequest;
use App\Http\Requests\Transfers\DispatchTransferRequest;
use App\Http\Requests\Transfers\ReceiveTransferRequest;
use App\Http\Requests\Transfers\TransferStoreRequest;
use App\Models\Location;
use App\Models\ProductVariant;
use App\Models\Transfer;
use App\Models\TransferStatusHistory;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransferController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Transfer::class);

        $user = $request->user();

        $transfers = Transfer::query()
            ->with(['sourceLocation:id,name', 'destinationLocation:id,name', 'requester:id,name'])
            ->withCount('items')
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when(! $user->isAdmin(), function ($query) use ($user) {
                $locationIds = $user->assignedLocations()->pluck('locations.id')->push($user->default_location_id)->filter()->unique();

                $query->where(function ($inner) use ($locationIds) {
                    $inner->whereIn('source_location_id', $locationIds)
                        ->orWhereIn('destination_location_id', $locationIds);
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('transfers/index', [
            'transfers' => $transfers,
            'statuses' => collect(TransferStatus::cases())->map(fn ($status) => ['label' => Str::headline($status->value), 'value' => $status->value]),
            'filters' => $request->only(['status']),
            'canCreate' => $request->user()->can('create', Transfer::class),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Transfer::class);

        return Inertia::render('transfers/form', [
            'sourceLocations' => Location::query()->where('type', LocationType::Warehouse)->where('is_active', true)->get(['id', 'name', 'code']),
            'destinationLocations' => Location::query()->where('type', LocationType::Shop)->where('is_active', true)->get(['id', 'name', 'code']),
            'variants' => ProductVariant::query()->with('product:id,brand_name')->where('is_active', true)->orderBy('sku')->get(['id', 'product_id', 'sku', 'color', 'meter_length']),
        ]);
    }

    public function store(TransferStoreRequest $request, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorize('create', Transfer::class);

        $source = Location::query()->findOrFail($request->integer('source_location_id'));
        $destination = Location::query()->findOrFail($request->integer('destination_location_id'));

        if ($source->type !== LocationType::Warehouse || $destination->type !== LocationType::Shop) {
            throw ValidationException::withMessages([
                'destination_location_id' => 'Transfers must move from warehouse to shop only.',
            ]);
        }

        $transfer = DB::transaction(function () use ($request, $auditLogService, $source, $destination) {
            $transfer = Transfer::create([
                'code' => 'TRF-'.now()->format('Ym').'-'.Str::upper(Str::random(6)),
                'source_location_id' => $source->id,
                'destination_location_id' => $destination->id,
                'status' => TransferStatus::PendingApproval,
                'requested_by' => $request->user()->id,
                'notes' => $request->input('notes'),
            ]);

            foreach ($request->validated('items') as $item) {
                $transfer->items()->create([
                    'product_variant_id' => $item['product_variant_id'],
                    'requested_quantity' => $item['requested_quantity'],
                    'approved_quantity' => null,
                    'dispatched_quantity' => 0,
                    'received_quantity' => 0,
                    'variance_quantity' => 0,
                ]);
            }

            TransferStatusHistory::create([
                'transfer_id' => $transfer->id,
                'from_status' => TransferStatus::Draft->value,
                'to_status' => TransferStatus::PendingApproval->value,
                'acted_by' => $request->user()->id,
                'reason' => 'Submitted for admin approval',
            ]);

            $auditLogService->record(
                $request->user(),
                'transfer',
                'transfer.created',
                'Created transfer '.$transfer->code,
                $transfer,
                $destination,
                ['transfer_code' => $transfer->code],
                $request,
            );

            return $transfer;
        });

        return redirect()->route('transfers.show', $transfer)->with('success', 'Transfer request created.');
    }

    public function show(Transfer $transfer, Request $request): Response
    {
        $this->authorize('view', $transfer);

        $transfer->load([
            'sourceLocation:id,name,code,type',
            'destinationLocation:id,name,code,type',
            'requester:id,name',
            'approver:id,name',
            'dispatcher:id,name',
            'receiver:id,name',
            'closer:id,name',
            'items.productVariant.product',
            'histories.actor:id,name',
        ]);

        return Inertia::render('transfers/show', [
            'transfer' => $transfer,
            'canApprove' => $request->user()->can('approve', $transfer),
            'canDispatch' => $request->user()->can('dispatch', $transfer),
            'canReceive' => $request->user()->can('receive', $transfer),
            'canCloseVariance' => $request->user()->can('closeVariance', $transfer),
        ]);
    }

    public function approve(Transfer $transfer, Request $request, ApproveTransferAction $action): RedirectResponse
    {
        $this->authorize('approve', $transfer);
        $action($transfer, $request->user());

        return back()->with('success', 'Transfer approved.');
    }

    public function dispatch(Transfer $transfer, DispatchTransferRequest $request, DispatchTransferAction $action): RedirectResponse
    {
        $this->authorize('dispatch', $transfer);
        $action($transfer, $request->user(), $request->validated('items'), $request->input('notes'));

        return back()->with('success', 'Transfer dispatched successfully.');
    }

    public function receive(Transfer $transfer, ReceiveTransferRequest $request, ConfirmTransferReceiptAction $action): RedirectResponse
    {
        $this->authorize('receive', $transfer);
        $action($transfer, $request->user(), $request->validated('items'), $request->input('notes'));

        return back()->with('success', 'Transfer receipt confirmed.');
    }

    public function close(Transfer $transfer, CloseTransferRequest $request, CloseTransferAction $action): RedirectResponse
    {
        $this->authorize('closeVariance', $transfer);
        $action($transfer, $request->user(), $request->input('notes'));

        return back()->with('success', 'Transfer closed successfully.');
    }
}
