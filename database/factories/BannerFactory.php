<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image_path' => 'banners/'.fake()->uuid().'.jpg',
            'mobile_image_path' => null,
            'headline' => fake()->sentence(4),
            'link_url' => null,
            'is_active' => true,
            'position' => 0,
        ];
    }
}
