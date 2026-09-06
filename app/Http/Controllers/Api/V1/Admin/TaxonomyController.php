<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Models\Brand;
use App\Models\Category;
use App\Services\Media\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Categories and brands.
 *
 * Both are full CRUD. In the previous system categories could only be edited or
 * deleted -- the create and store actions were empty stubs and the action
 * column was commented out of the view, so the set was effectively frozen at
 * whatever the seeder happened to insert.
 */
class TaxonomyController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function categories(Request $request): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::query()
                ->with(['children'])
                ->when(! $request->boolean('flat'), fn ($query) => $query->whereNull('parent_id'))
                ->orderBy('position')
                ->orderBy('name')
                ->get()
        );
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $category = Category::query()->create($this->categoryRules($request));

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function updateCategory(Request $request, Category $category): CategoryResource
    {
        $category->update($this->categoryRules($request, $category));

        return new CategoryResource($category->fresh());
    }

    public function destroyCategory(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Move or remove this category\'s products before deleting it.',
            ], 422);
        }

        $this->media->delete($category->image_path);
        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }

    public function brands(Request $request): AnonymousResourceCollection
    {
        return BrandResource::collection(
            Brand::query()->orderBy('position')->orderBy('name')->get()
        );
    }

    public function storeBrand(Request $request): JsonResponse
    {
        $brand = Brand::query()->create($this->brandRules($request));

        return (new BrandResource($brand))->response()->setStatusCode(201);
    }

    public function updateBrand(Request $request, Brand $brand): BrandResource
    {
        $brand->update($this->brandRules($request, $brand));

        return new BrandResource($brand->fresh());
    }

    public function destroyBrand(Brand $brand): JsonResponse
    {
        if ($brand->products()->exists()) {
            return response()->json([
                'message' => 'Move or remove this brand\'s products before deleting it.',
            ], 422);
        }

        $this->media->delete($brand->logo_path);
        $brand->delete();

        return response()->json(['message' => 'Brand deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function categoryRules(Request $request, ?Category $category = null): array
    {
        $validated = $request->validate([
            'name' => [$category === null ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', Rule::notIn([$category?->id])],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $this->media->replace($category?->image_path, $request->file('image'), 'categories');
        }

        unset($validated['image']);

        if (isset($validated['name'])) {
            $validated['slug'] ??= Str::slug($validated['name']);
        }

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function brandRules(Request $request, ?Brand $brand = null): array
    {
        $validated = $request->validate([
            'name' => [$brand === null ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($brand?->id)],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp,svg', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $this->media->replace($brand?->logo_path, $request->file('logo'), 'brands');
        }

        unset($validated['logo']);

        if (isset($validated['name'])) {
            $validated['slug'] ??= Str::slug($validated['name']);
        }

        return $validated;
    }
}
