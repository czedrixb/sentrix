<?php

namespace App\Support;

/**
 * Money helpers.
 *
 * All arithmetic in the pricing engine happens in integer centavos so that
 * repeated percentage and rounding operations cannot drift, which is what
 * float-based totals in the previous system allowed.
 */
final class Money
{
    /**
     * Convert a decimal amount (as stored on the model) into centavos.
     */
    public static function toCentavos(int|float|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    /**
     * Convert centavos back into a 2dp decimal string suitable for storage.
     */
    public static function toDecimal(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }

    /**
     * Apply a percentage to a centavo amount, rounding half up once.
     */
    public static function percentageOf(int $centavos, int|float|string $percent): int
    {
        return (int) round($centavos * ((float) $percent) / 100);
    }

    /**
     * Clamp a value to the inclusive range.
     */
    public static function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
