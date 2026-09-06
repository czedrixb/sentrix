<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'fulfillment_type' => $this->fulfillment_type->value,
            'fulfillment_label' => $this->fulfillment_type->label(),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'customer' => [
                'first_name' => $this->customer_first_name,
                'last_name' => $this->customer_last_name,
                'email' => $this->customer_email,
                'phone' => $this->customer_phone,
            ],
            'delivery_address' => $this->delivery_address,
            'notes' => $this->notes,
            'scheduled_for' => $this->scheduled_for?->toIso8601String(),
            'totals' => [
                'subtotal' => $this->subtotal,
                'discount_total' => $this->discount_total,
                'delivery_fee' => $this->delivery_fee,
                'grand_total' => $this->grand_total,
            ],
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'archived_at' => $this->archived_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
