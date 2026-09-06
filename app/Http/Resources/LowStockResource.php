<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One product/branch pair sitting at or below its low-stock threshold.
 *
 * The underlying row comes from a joined query builder result rather than a
 * model, because the figure being reported lives on the branch_product pivot
 * and hydrating two models per row to read it would be wasteful.
 */
class LowStockResource extends JsonResource
{
    /**
     * @return array{
     *     product_id: int,
     *     product_name: string,
     *     sku: string,
     *     branch_id: int,
     *     branch_name: string,
     *     quantity: int,
     *     low_stock_threshold: int
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => (int) $this->product_id,
            'product_name' => $this->product_name,
            'sku' => $this->sku,
            'branch_id' => (int) $this->branch_id,
            'branch_name' => $this->branch_name,
            'quantity' => (int) $this->quantity,
            'low_stock_threshold' => (int) $this->low_stock_threshold,
        ];
    }
}
