<?php

namespace App\Http\Resources;

use App\Models\Cart;
use App\Services\Pricing\CartTotals;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A cart together with its server-computed totals.
 *
 * The totals are always taken from the pricing service; the client has no way
 * to influence them.
 *
 * @mixin Cart
 */
class CartResource extends JsonResource
{
    public function __construct($resource, private readonly ?CartTotals $totals = null)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'fulfillment_type' => $this->fulfillment_type?->value,
            'requires_delivery' => $this->requiresDelivery(),
            'item_count' => (int) $this->items->sum('quantity'),
            'items' => $this->totals !== null
                ? $this->linesFromTotals()
                : CartItemResource::collection($this->whenLoaded('items')),
            'voucher' => $this->totals?->voucher !== null ? [
                'code' => $this->totals->voucher->code,
                'type' => $this->totals->voucher->type->value,
                'description' => $this->totals->voucher->description,
            ] : null,
            'voucher_rejection_reason' => $this->totals?->voucherRejectionReason,
            'totals' => $this->totals?->toDecimals() ?? [
                'subtotal' => '0.00',
                'discount_total' => '0.00',
                'delivery_fee' => '0.00',
                'grand_total' => '0.00',
            ],
        ];
    }

    /**
     * Priced lines, so the SPA renders exactly what the server calculated.
     *
     * @return list<array<string, mixed>>
     */
    private function linesFromTotals(): array
    {
        $itemsByProduct = $this->items->keyBy('product_id');

        return array_map(function ($line) use ($itemsByProduct): array {
            $item = $itemsByProduct->get($line->product->id);

            return [
                'id' => $item?->id,
                'product' => new ProductResource($line->product->loadMissing(['images', 'category', 'brand'])),
                'quantity' => $line->quantity,
                'unit_price' => Money::toDecimal($line->unitPriceCentavos),
                'line_total' => Money::toDecimal($line->lineTotalCentavos),
                'discount_eligible' => $line->isVoucherEligible,
            ];
        }, $this->totals->lines);
    }
}
