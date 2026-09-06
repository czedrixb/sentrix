<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
        return [
            'sku' => ['required', 'string', 'max:64', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'is_featured' => ['nullable', 'boolean'],
            'is_bundle' => ['nullable', 'boolean'],
            'requires_delivery' => ['nullable', 'boolean'],
            'length_cm' => ['nullable', 'numeric', 'min:0'],
            'width_cm' => ['nullable', 'numeric', 'min:0'],
            'height_cm' => ['nullable', 'numeric', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::enum(ProductStatus::class)],

            // Images are validated before a single byte is written to disk.
            'images' => ['nullable', 'array', 'max:12'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],

            // Stock is a branch_id => quantity map, so any branch works.
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
     * The product columns only, with a slug derived when one is not supplied.
     *
     * @return array<string, mixed>
     */
    public function productAttributes(): array
    {
        $validated = $this->safe()->except(['images', 'stock', 'bundle_items']);

        $validated['slug'] ??= Str::slug($validated['name']);
        $validated['status'] ??= ProductStatus::Draft->value;

        return $validated;
    }
}
