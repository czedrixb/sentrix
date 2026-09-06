<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city().' Branch';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'address' => fake()->address(),
            'phone' => '+639'.fake()->numerify('#########'),
            'support_phone' => null,
            'sales_phone' => null,
            'email' => fake()->unique()->companyEmail(),
            'secondary_email' => null,
            'map_embed' => null,
            'is_pickup_location' => true,
            'is_active' => true,
            'position' => 0,
        ];
    }
}
