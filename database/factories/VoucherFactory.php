<?php

namespace Database\Factories;

use App\Enums\VoucherType;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE####')),
            'description' => fake()->sentence(4),
            'type' => VoucherType::Percentage,
            'value' => 10,
            'min_quantity' => null,
            'min_subtotal' => null,
            'usage_limit' => null,
            'times_used' => 0,
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
        ];
    }

    public function percentage(float $percent = 10): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => VoucherType::Percentage,
            'value' => $percent,
        ]);
    }

    public function fixed(float $amount = 500): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => VoucherType::Fixed,
            'value' => $amount,
        ]);
    }

    public function percentageWithMinimumQuantity(float $percent = 10, int $minQuantity = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => VoucherType::PercentageMinQuantity,
            'value' => $percent,
            'min_quantity' => $minQuantity,
        ]);
    }

    public function fixedWithMinimumQuantity(float $amount = 500, int $minQuantity = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => VoucherType::FixedMinQuantity,
            'value' => $amount,
            'min_quantity' => $minQuantity,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);
    }
}
