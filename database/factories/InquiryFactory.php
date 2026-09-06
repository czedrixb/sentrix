<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '9'.fake()->numerify('#########'),
            'message' => fake()->paragraph(),
            'handled_at' => null,
        ];
    }

    /**
     * A general enquiry not addressed to any particular branch.
     */
    public function general(): static
    {
        return $this->state(fn (array $attributes): array => ['branch_id' => null]);
    }
}
