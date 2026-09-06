<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));

        return [
            'sku' => strtoupper(fake()->unique()->bothify('??-####')),
            'name' => $name,
            'slug' => Str::slug($name),
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
            'short_description' => fake()->sentence(),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'price' => fake()->randomFloat(2, 150, 90000),
            'is_featured' => false,
            'is_bundle' => false,
            'requires_delivery' => false,
            'length_cm' => fake()->randomFloat(2, 5, 120),
            'width_cm' => fake()->randomFloat(2, 5, 80),
            'height_cm' => fake()->randomFloat(2, 5, 80),
            'weight_kg' => fake()->randomFloat(3, 0.2, 60),
            'status' => ProductStatus::Active,
        ];
    }

    /**
     * A product highlighted on the storefront home page.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => ['is_featured' => true]);
    }

    /**
     * A bulky product that can only be fulfilled by delivery.
     */
    public function requiresDelivery(): static
    {
        return $this->state(fn (array $attributes): array => ['requires_delivery' => true]);
    }

    /**
     * A bundle parent, whose contents live in bundle_items.
     */
    public function bundle(): static
    {
        return $this->state(fn (array $attributes): array => ['is_bundle' => true]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => ProductStatus::Archived]);
    }
}
