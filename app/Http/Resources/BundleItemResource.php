<?php

namespace App\Http\Resources;

use App\Models\BundleItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BundleItem
 */
class BundleItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'quantity' => $this->quantity,
            'product_id' => $this->product_id,
            'product_slug' => $this->whenLoaded('product', fn (): ?string => $this->product?->slug),
        ];
    }
}
