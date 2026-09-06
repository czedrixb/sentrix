<?php

namespace App\Enums;

enum VoucherType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
    case PercentageMinQuantity = 'percentage_min_qty';
    case FixedMinQuantity = 'fixed_min_qty';

    /**
     * Human readable label for admin UIs.
     */
    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage off',
            self::Fixed => 'Fixed amount off',
            self::PercentageMinQuantity => 'Percentage off, minimum quantity',
            self::FixedMinQuantity => 'Fixed amount off, minimum quantity',
        };
    }

    /**
     * Whether the voucher only applies once a minimum quantity is met.
     */
    public function requiresMinimumQuantity(): bool
    {
        return in_array($this, [self::PercentageMinQuantity, self::FixedMinQuantity], true);
    }

    /**
     * Whether the discount is expressed as a percentage rather than an amount.
     */
    public function isPercentage(): bool
    {
        return in_array($this, [self::Percentage, self::PercentageMinQuantity], true);
    }
}
