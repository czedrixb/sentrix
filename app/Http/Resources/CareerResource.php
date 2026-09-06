<?php

namespace App\Http\Resources;

use App\Models\Career;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Career
 */
class CareerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'vacancies' => $this->vacancies,
            'employment_type' => $this->employment_type,
            'description' => $this->description,
            'apply_email' => $this->apply_email,
            'is_open' => $this->is_open,
            'branch' => new BranchResource($this->whenLoaded('branch')),
        ];
    }
}
