<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'price' => $this->price,
            'is_featured' => $this->is_featured,
            'is_bundle' => $this->is_bundle,
            'requires_delivery' => $this->requires_delivery,
            'status' => $this->status->value,
            'dimensions' => [
                'length_cm' => $this->length_cm,
                'width_cm' => $this->width_cm,
                'height_cm' => $this->height_cm,
                'weight_kg' => $this->weight_kg,
            ],
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'bundle_items' => BundleItemResource::collection($this->whenLoaded('bundleItems')),

            // Stock is per branch. When the request names a branch we expose the
            // level for that branch only; otherwise the full breakdown.
            'stock' => $this->when(
                $this->relationLoaded('branches'),
                fn (): array => $this->branches->map(fn ($branch): array => [
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'branch_slug' => $branch->slug,
                    'quantity' => (int) $branch->pivot->quantity,
                    'low_stock_threshold' => (int) $branch->pivot->low_stock_threshold,
                ])->values()->all()
            ),
            // Only present when the listing was scoped to a single branch.
            'available_quantity' => $this->when(
                array_key_exists('available_quantity', $this->resource->getAttributes()),
                fn (): int => (int) $this->resource->getAttributes()['available_quantity']
            ),
        ];
    }
}
