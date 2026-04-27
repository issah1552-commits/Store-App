<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\InternalMovements\ApproveInternalMovementAction;
use App\Actions\InternalMovements\CreateInternalMovementAction;
use App\Actions\InternalMovements\ReverseInternalMovementAction;
use App\Enums\InternalMovementStatus;
use App\Enums\StockBucket;
use App\Http\Controllers\Controller;
use App\Http\Requests\InternalMovements\InternalMovementStoreRequest;
use App\Models\InternalMovement;
use App\Models\InternalMovementHistory;
use App\Models\Location;
use App\Models\Stock;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InternalMovementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', InternalMovement::class);

        $user = $request->user();
        $locationIds = $user->assignedLocations()->pluck('locations.id')->push($user->default_location_id)->filter()->unique();

        $movements = InternalMovement::query()
            ->with(['location:id,name', 'requester:id,name'])
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when(! $user->isAdmin(), fn ($query) => $query->whereIn('location_id', $locationIds))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('internal-movements/index', [
            'movements' => $movements,
            'statuses' => collect(InternalMovementStatus::cases())->map(fn ($status) => ['label' => str($status->value)->headline()->toString(), 'value' => $status->value]),
            'filters' => $request->only(['status']),
            'canCreate' => $request->user()->can('create', InternalMovement::class),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', InternalMovement::class);

        $locationIds = $request->user()->assignedLocations()->pluck('locations.id')->push($request->user()->default_location_id)->filter()->unique();

        return Inertia::render('internal-movements/form', [
            'locations' => Location::query()->whereIn('id', $locationIds)->where('type', 'shop')->get(['id', 'name', 'code']),
            'variants' => Stock::query()
                ->with(['productVariant.product'])
                ->whereIn('location_id', $locationIds)
                ->where('bucket', StockBucket::Wholesale)
                ->where('quantity', '>', 0)
                ->get()
                ->map(fn ($stock) => [
                    'location_id' => $stock->location_id,
                    'product_variant_id' => $stock->product_variant_id,
                    'sku' => $stock->productVariant->sku,
                    'brand_name' => $stock->productVariant->product->brand_name,
                    'color' => $stock->productVariant->color,
                    'meter_length' => $stock->productVariant->meter_length,
                    'available_quantity' => $stock->quantity,
                ]),
        ]);
    }

    public function store(InternalMovementStoreRequest $request, CreateInternalMovementAction $action): RedirectResponse
    {
        $this->authorize('create', InternalMovement::class);

        $location = Location::query()->findOrFail($request->integer('location_id'));

        if (! $request->user()->canAccessLocation($location)) {
            throw ValidationException::withMessages([
                'location_id' => 'You cannot create internal movements for this shop.',
            ]);
        }

        $movement = $action($request->user(), $location, $request->validated());

        return redirect()->route('internal-movements.show', $movement)->with('success', 'Internal movement created.');
    }

    public function show(InternalMovement $internalMovement, Request $request): Response
    {
        $this->authorize('view', $internalMovement);

        $internalMovement->load([
            'location:id,name,code',
            'requester:id,name',
            'approver:id,name',
            'completer:id,name',
            'items.productVariant.product',
            'histories.actor:id,name',
        ]);

        return Inertia::render('internal-movements/show', [
            'movement' => $internalMovement,
            'canApprove' => $request->user()->can('approve', $internalMovement),
            'canReverse' => $request->user()->can('reverse', $internalMovement),
        ]);
    }

    public function approve(InternalMovement $internalMovement, Request $request, ApproveInternalMovementAction $action): RedirectResponse
    {
        $this->authorize('approve', $internalMovement);
        $action($internalMovement, $request->user(), $request->input('notes'));

        return back()->with('success', 'Internal movement approved and completed.');
    }

    public function reject(InternalMovement $internalMovement, Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorize('approve', $internalMovement);

        if ($internalMovement->status !== InternalMovementStatus::Escalated) {
            throw ValidationException::withMessages([
                'status' => 'Only escalated movements can be rejected.',
            ]);
        }

        DB::transaction(function () use ($internalMovement, $request, $auditLogService) {
            $internalMovement->update([
                'status' => InternalMovementStatus::Rejected,
                'rejected_by' => $request->user()->id,
                'rejected_at' => now(),
                'notes' => $request->input('notes', $internalMovement->notes),
            ]);

            InternalMovementHistory::create([
                'internal_movement_id' => $internalMovement->id,
                'from_status' => InternalMovementStatus::Escalated->value,
                'to_status' => InternalMovementStatus::Rejected->value,
                'acted_by' => $request->user()->id,
                'reason' => $request->input('notes', 'Escalated movement rejected'),
            ]);

            $auditLogService->record(
                $request->user(),
                'internal_movement',
                'internal_movement.rejected',
                'Rejected internal movement '.$internalMovement->code,
                $internalMovement,
                $internalMovement->location,
                ['code' => $internalMovement->code],
                $request,
            );
        });

        return back()->with('success', 'Internal movement rejected.');
    }

    public function reverse(InternalMovement $internalMovement, Request $request, ReverseInternalMovementAction $action): RedirectResponse
    {
        $this->authorize('reverse', $internalMovement);
        $action($internalMovement, $request->user(), $request->input('notes'));

        return back()->with('success', 'Internal movement reversed.');
    }
}
