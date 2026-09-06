<?php

namespace App\Http\Resources;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Banner
 */
class BannerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url(),
            'mobile_url' => $this->mobile_image_path
                ? Storage::disk('public')->url($this->mobile_image_path)
                : null,
            'headline' => $this->headline,
            'link_url' => $this->link_url,
            'position' => $this->position,
        ];
    }
}
