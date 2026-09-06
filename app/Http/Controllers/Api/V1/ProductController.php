<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Browse the catalogue.
     *
     * Filters compose properly here. The previous implementation chained an
     * ungrouped orWhere for the archive flag, which silently defeated the
     * branch and category filters applied before it.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $branch = $this->resolveBranch($request);

        $products = Product::query()
            ->active()
            ->with(['brand', 'category', 'images'])
            ->when($branch, fn (Builder $query) => $query->availableAt($branch->id))
            ->when($request->filled('category'), function (Builder $query) use ($request): void {
                $slugs = (array) $request->input('category');
                $query->whereHas('category', fn (Builder $c) => $c->whereIn('slug', $slugs));
            })
            ->when($request->filled('brand'), function (Builder $query) use ($request): void {
                $slugs = (array) $request->input('brand');
                $query->whereHas('brand', fn (Builder $b) => $b->whereIn('slug', $slugs));
            })
            ->when($request->boolean('featured'), fn (Builder $query) => $query->where('is_featured', true))
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';

                // Grouped, so the search never widens the other filters.
                $query->where(function (Builder $search) use ($term): void {
                    $search->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('short_description', 'like', $term)
                        ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', $term))
                        ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', $term));
                });
            });

        $this->applySort($products, $request->string('sort')->toString());

        if ($branch !== null) {
            $products->withAvailableQuantityAt($branch->id);
        }

        return ProductResource::collection(
            $products->paginate($request->integer('per_page', 15))->withQueryString()
        );
    }

    /**
     * A single product, with its per-branch stock breakdown.
     */
    public function show(Request $request, Product $product): ProductResource
    {
        abort_unless($product->status->value === 'active', 404);

        $product->load(['brand', 'category', 'images', 'bundleItems.product', 'branches']);

        return new ProductResource($product);
    }

    /**
     * Products related to the given one, used on the product page.
     */
    public function related(Request $request, Product $product): AnonymousResourceCollection
    {
        $branch = $this->resolveBranch($request);

        $related = Product::query()
            ->active()
            ->with(['brand', 'category', 'images'])
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->when($branch, fn (Builder $query) => $query->availableAt($branch->id))
            ->latest()
            ->limit(8)
            ->get();

        return ProductResource::collection($related);
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };
    }

    private function resolveBranch(Request $request): ?Branch
    {
        if (! $request->filled('branch')) {
            return null;
        }

        return Branch::query()->active()->where('slug', $request->string('branch'))->first();
    }
}
