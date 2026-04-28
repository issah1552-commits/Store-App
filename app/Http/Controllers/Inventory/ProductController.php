<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\LocationType;
use App\Enums\StockBucket;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ProductStoreRequest;
use App\Http\Requests\Products\ProductUpdateRequest;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AuditLogService;
use App\Services\Inventory\StockService;
use App\Services\LocationContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request, LocationContextService $locationContext): Response
    {
        $this->authorize('viewAny', Product::class);

        $user = $request->user()->loadMissing('assignedLocations', 'defaultLocation');
        $locationIds = $locationContext->scopedLocationIds($request, $user)->all();

        $stockBuckets = $user->isWarehouseUser()
            ? [StockBucket::Warehouse->value, StockBucket::InTransit->value]
            : [StockBucket::Wholesale->value, StockBucket::Retail->value];

        $stockStatus = $request->string('stock_status')->toString();

        $variantStatusQuery = ProductVariant::query()
            ->selectRaw('product_variants.product_id, product_variants.id as variant_id, product_variants.low_stock_threshold, COALESCE(SUM(stocks.quantity), 0) as total_quantity')
            ->leftJoin('stocks', function ($join) use ($locationIds, $stockBuckets) {
                $join->on('stocks.product_variant_id', '=', 'product_variants.id')
                    ->whereIn('stocks.location_id', $locationIds)
                    ->whereIn('stocks.bucket', $stockBuckets);
            })
            ->where('product_variants.is_active', true)
            ->groupBy('product_variants.product_id', 'product_variants.id', 'product_variants.low_stock_threshold');

        $query = Product::query()
            ->with([
                'category:id,name',
                'variants' => fn ($variantQuery) => $variantQuery
                    ->where('is_active', true)
                    ->with(['stocks' => fn ($stockQuery) => $stockQuery
                        ->whereIn('location_id', $locationIds)
                        ->whereIn('bucket', $stockBuckets)
                        ->with('location:id,name,code,type')]),
            ])
            ->when($request->string('search')->toString(), fn ($builder, $search) => $builder->where('brand_name', 'like', '%'.$search.'%'))
            ->when($request->integer('category_id'), fn ($builder, $categoryId) => $builder->where('category_id', $categoryId))
            ->when($stockStatus, function ($builder) use ($stockStatus, $variantStatusQuery) {
                $productStatusQuery = DB::query()
                    ->fromSub($variantStatusQuery, 'variant_status')
                    ->select('variant_status.product_id')
                    ->groupBy('variant_status.product_id');

                match ($stockStatus) {
                    'out_of_stock' => $productStatusQuery->havingRaw('MAX(variant_status.total_quantity) = 0'),
                    'low_stock' => $productStatusQuery->havingRaw('SUM(CASE WHEN variant_status.total_quantity > 0 AND variant_status.total_quantity <= variant_status.low_stock_threshold THEN 1 ELSE 0 END) > 0'),
                    'in_stock' => $productStatusQuery->havingRaw('SUM(CASE WHEN variant_status.total_quantity > variant_status.low_stock_threshold THEN 1 ELSE 0 END) > 0'),
                    default => null,
                };

                $builder->whereIn('products.id', $productStatusQuery);
            })
            ->where('is_active', true)
            ->orderBy('brand_name');

        $products = $query->paginate(10)->withQueryString()->through(function (Product $product) {
            $variants = $product->variants->map(function (ProductVariant $variant) {
                $totalStock = $variant->stocks->sum('quantity');

                return [
                    'id' => $variant->id,
                    'meter_length' => $variant->meter_length,
                    'total_stock' => $totalStock,
                    'total_meters' => (float) $variant->meter_length * $totalStock,
                ];
            });

            return [
                'id' => $product->id,
                'brand_name' => $product->brand_name,
                'total_stock' => $variants->sum('total_stock'),
                'total_meters' => $variants->sum('total_meters'),
            ];
        });

        return Inertia::render('products/index', [
            'products' => $products,
            'filters' => $request->only(['search', 'category_id', 'stock_status']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'canManageProducts' => $request->user()->can('create', Product::class),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('products/form', [
            'mode' => 'create',
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(ProductStoreRequest $request, StockService $stockService, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $actor = $request->user()->loadMissing('defaultLocation');
        abort_unless($actor->defaultLocation?->type === LocationType::Warehouse || $actor->isAdmin(), 403);

        DB::transaction(function () use ($request, $stockService, $auditLogService, $actor) {
            $product = Product::create([
                'brand_name' => $request->string('brand_name')->toString(),
                'category_id' => $request->integer('category_id'),
                'description' => $request->input('description'),
                'is_active' => true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach ($request->validated('variants') as $variantData) {
                $variant = $product->variants()->create([
                    'sku' => strtoupper(Str::slug($product->brand_name.'-'.$variantData['color'].'-'.$variantData['meter_length'], '-')),
                    'color' => $variantData['color'],
                    'meter_length' => $variantData['meter_length'],
                    'standard_cost_tzs' => $variantData['standard_cost_tzs'],
                    'wholesale_price_tzs' => $variantData['wholesale_price_tzs'],
                    'retail_price_tzs' => $variantData['retail_price_tzs'],
                    'low_stock_threshold' => $variantData['low_stock_threshold'] ?? 5,
                    'is_active' => true,
                ]);

                $stockService->addStock(
                    actor: $actor,
                    variant: $variant,
                    destinationLocation: $actor->defaultLocation,
                    destinationBucket: StockBucket::Warehouse,
                    quantity: (int) $variantData['rolls'],
                    movementType: StockMovementType::OpeningBalance,
                    reference: $product,
                    notes: 'Opening stock created from product setup',
                    meta: ['sku' => $variant->sku],
                );
            }

            $auditLogService->record(
                $actor,
                'product',
                'product.created',
                'Created product '.$product->brand_name,
                $product,
                $actor->defaultLocation,
                ['brand_name' => $product->brand_name],
                $request,
            );
        });

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function show(Product $product, Request $request): Response
    {
        $this->authorize('view', $product);

        $product->load([
            'category:id,name',
            'variants.stocks.location:id,name,code,type',
        ]);

        return Inertia::render('products/show', [
            'product' => $product,
            'canEdit' => $request->user()->can('update', $product),
        ]);
    }

    public function edit(Product $product): Response
    {
        $this->authorize('update', $product);

        $product->load(['category:id,name', 'variants']);

        return Inertia::render('products/form', [
            'mode' => 'edit',
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(ProductUpdateRequest $request, Product $product, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorize('update', $product);

        DB::transaction(function () use ($request, $product, $auditLogService) {
            $product->loadMissing('variants');

            $product->update([
                'brand_name' => $request->string('brand_name')->toString(),
                'category_id' => $request->integer('category_id'),
                'description' => $request->input('description'),
                'updated_by' => $request->user()->id,
            ]);

            $variantIdsToKeep = [];

            foreach ($request->validated('variants') as $variantData) {
                $attributes = [
                    'sku' => $variantData['sku'] ?? strtoupper(Str::slug($product->brand_name.'-'.$variantData['color'].'-'.$variantData['meter_length'], '-')),
                    'color' => $variantData['color'],
                    'meter_length' => $variantData['meter_length'],
                    'standard_cost_tzs' => $variantData['standard_cost_tzs'],
                    'wholesale_price_tzs' => $variantData['wholesale_price_tzs'],
                    'retail_price_tzs' => $variantData['retail_price_tzs'],
                    'low_stock_threshold' => $variantData['low_stock_threshold'] ?? 5,
                    'is_active' => true,
                ];

                $variant = isset($variantData['id'])
                    ? $product->variants()->whereKey($variantData['id'])->firstOrFail()
                    : $product->variants()->make();

                $variant->fill($attributes);
                $variant->save();

                $variantIdsToKeep[] = $variant->id;
            }

            $product->variants()->whereNotIn('id', $variantIdsToKeep)->update(['is_active' => false]);

            $auditLogService->record(
                $request->user(),
                'product',
                'product.updated',
                'Updated product '.$product->brand_name,
                $product,
                $request->user()->defaultLocation,
                ['brand_name' => $product->brand_name],
                $request,
            );
        });

        return redirect()->route('products.show', $product)->with('success', 'Product updated successfully.');
    }
}
