<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\BranchResource;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\GalleryImageResource;
use App\Models\Banner;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\GalleryImage;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read-only reference data the storefront needs to render itself.
 *
 * Branches, categories and brands are all data. Nothing about the storefront
 * assumes a fixed set of any of them, so adding a branch or a product line is
 * an admin action rather than a deployment.
 */
class ReferenceController extends Controller
{
    public function branches(): AnonymousResourceCollection
    {
        return BranchResource::collection(
            Branch::query()->active()->orderBy('position')->orderBy('name')->get()
        );
    }

    public function categories(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::query()
                ->active()
                ->whereNull('parent_id')
                ->with(['children' => fn ($query) => $query->where('is_active', true)])
                ->orderBy('position')
                ->get()
        );
    }

    public function brands(): AnonymousResourceCollection
    {
        return BrandResource::collection(
            Brand::query()->active()->orderBy('position')->orderBy('name')->get()
        );
    }

    public function banners(): AnonymousResourceCollection
    {
        return BannerResource::collection(
            Banner::query()->active()->orderBy('position')->get()
        );
    }

    public function gallery(): AnonymousResourceCollection
    {
        return GalleryImageResource::collection(
            GalleryImage::query()->active()->orderBy('position')->get()
        );
    }
}
