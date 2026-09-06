<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Refunded = 'refunded';
    case Failed = 'failed';

    /**
     * Human readable label for admin UIs.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Unpaid',
            self::Paid => 'Paid',
            self::Refunded => 'Refunded',
            self::Failed => 'Failed',
        };
    }
}
