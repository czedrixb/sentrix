<?php

namespace Database\Factories;

use App\Models\BundleItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BundleItem>
 */
class BundleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bundle_product_id' => Product::factory()->bundle(),
            'product_id' => null,
            'label' => fake()->words(2, true),
            'quantity' => 1,
            'position' => 0,
        ];
    }
}
