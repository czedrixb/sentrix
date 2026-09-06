<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreProductRequest;
use App\Http\Requests\Api\Admin\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Media\MediaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with(['brand', 'category', 'images', 'branches'])
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')))
            ->when($request->filled('category_id'), fn (Builder $q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('brand_id'), fn (Builder $q) => $q->where('brand_id', $request->integer('brand_id')))
            ->when($request->boolean('bundles_only'), fn (Builder $q) => $q->where('is_bundle', true))
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';

                $query->where(fn (Builder $s) => $s->where('name', 'like', $term)->orWhere('sku', 'like', $term));
            })
            ->latest()
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return ProductResource::collection($products);
    }

    /**
     * Create a product.
     *
     * Validation runs before anything touches disk, so a failed request cannot
     * leave orphaned uploads behind the way the previous implementation did.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = DB::transaction(function () use ($request): Product {
            $product = Product::query()->create($request->productAttributes());

            $this->syncImages($request, $product);
            $this->syncStock($request, $product);
            $this->syncBundleItems($request, $product);

            return $product;
        });

        return (new ProductResource($product->load(['brand', 'category', 'images', 'branches', 'bundleItems'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource(
            $product->load(['brand', 'category', 'images', 'branches', 'bundleItems.product'])
        );
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        DB::transaction(function () use ($request, $product): void {
            $product->update($request->productAttributes());

            $this->syncImages($request, $product);
            $this->syncStock($request, $product);
            $this->syncBundleItems($request, $product);
        });

        return new ProductResource(
            $product->fresh(['brand', 'category', 'images', 'branches', 'bundleItems'])
        );
    }

    /**
     * Archiving is a status change, not a delete. Deleting is soft, and it
     * takes the product's images with it.
     */
    public function archive(Product $product): ProductResource
    {
        $product->update(['status' => ProductStatus::Archived]);

        return new ProductResource($product->fresh());
    }

    public function restore(Product $product): ProductResource
    {
        $product->update(['status' => ProductStatus::Active]);

        return new ProductResource($product->fresh());
    }

    public function destroy(Product $product): JsonResponse
    {
        DB::transaction(function () use ($product): void {
            foreach ($product->images as $image) {
                $this->media->delete($image->path);
            }

            $product->images()->delete();
            $product->delete();
        });

        return response()->json(['message' => 'Product deleted.']);
    }

    /**
     * Set stock for one branch. Works for any branch, including one created
     * after this product was.
     */
    public function updateStock(Request $request, Product $product): ProductResource
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'quantity' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ]);

        $product->branches()->syncWithoutDetaching([
            $validated['branch_id'] => [
                'quantity' => $validated['quantity'],
                'low_stock_threshold' => $validated['low_stock_threshold'] ?? 0,
            ],
        ]);

        return new ProductResource($product->fresh(['branches']));
    }

    public function destroyImage(Product $product, ProductImage $image): JsonResponse
    {
        abort_unless($image->product_id === $product->id, 404);

        $this->media->delete($image->path);
        $image->delete();

        return response()->json(['message' => 'Image removed.']);
    }

    private function syncImages(Request $request, Product $product): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $position = (int) $product->images()->max('position');

        foreach ($request->file('images') as $file) {
            $product->images()->create([
                'path' => $this->media->store($file, 'products'),
                'is_primary' => $product->images()->count() === 0,
                'position' => ++$position,
            ]);
        }
    }

    /**
     * Stock arrives as a branch_id => quantity map, so a new branch needs no
     * new field, column or code path.
     */
    private function syncStock(Request $request, Product $product): void
    {
        $stock = $request->input('stock');

        if (! is_array($stock)) {
            return;
        }

        $payload = [];

        foreach ($stock as $row) {
            if (! isset($row['branch_id'])) {
                continue;
            }

            $payload[(int) $row['branch_id']] = [
                'quantity' => max(0, (int) ($row['quantity'] ?? 0)),
                'low_stock_threshold' => max(0, (int) ($row['low_stock_threshold'] ?? 0)),
            ];
        }

        if ($payload !== []) {
            $product->branches()->syncWithoutDetaching($payload);
        }
    }

    /**
     * Bundle contents, replacing the previous free-text inclusion list.
     */
    private function syncBundleItems(Request $request, Product $product): void
    {
        $items = $request->input('bundle_items');

        if (! is_array($items)) {
            return;
        }

        $product->bundleItems()->delete();

        foreach ($items as $position => $item) {
            if (blank($item['label'] ?? null)) {
                continue;
            }

            $product->bundleItems()->create([
                'label' => $item['label'],
                'product_id' => $item['product_id'] ?? null,
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                'position' => $position,
            ]);
        }
    }
}
