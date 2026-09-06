<?php

namespace Database\Factories;

use App\Models\Career;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Career>
 */
class CareerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->jobTitle();

        return [
            'branch_id' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'vacancies' => fake()->numberBetween(1, 5),
            'employment_type' => fake()->randomElement(['Full-time', 'Part-time', 'Contract']),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'apply_email' => fake()->companyEmail(),
            'is_open' => true,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => ['is_open' => false]);
    }
}
