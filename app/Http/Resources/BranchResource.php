<?php

namespace App\Http\Resources;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Branch
 */
class BranchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'address' => $this->address,
            'phone' => $this->phone,
            'support_phone' => $this->support_phone,
            'sales_phone' => $this->sales_phone,
            'email' => $this->email,
            'secondary_email' => $this->secondary_email,
            'map_embed' => $this->map_embed,
            'is_pickup_location' => $this->is_pickup_location,
            'position' => $this->position,
        ];
    }
}
