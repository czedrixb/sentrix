<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('products.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'sku' => ['sometimes', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($productId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0', 'max:99999999'],
            'is_featured' => ['nullable', 'boolean'],
            'is_bundle' => ['nullable', 'boolean'],
            'requires_delivery' => ['nullable', 'boolean'],
            'length_cm' => ['nullable', 'numeric', 'min:0'],
            'width_cm' => ['nullable', 'numeric', 'min:0'],
            'height_cm' => ['nullable', 'numeric', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::enum(ProductStatus::class)],

            'images' => ['nullable', 'array', 'max:12'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],

            'stock' => ['nullable', 'array'],
            'stock.*.branch_id' => ['required', 'integer', 'exists:branches,id'],
            'stock.*.quantity' => ['required', 'integer', 'min:0'],
            'stock.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],

            'bundle_items' => ['nullable', 'array'],
            'bundle_items.*.label' => ['required', 'string', 'max:255'],
            'bundle_items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'bundle_items.*.quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function productAttributes(): array
    {
        return $this->safe()->except(['images', 'stock', 'bundle_items']);
    }
}
