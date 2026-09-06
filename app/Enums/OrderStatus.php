<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Human readable label for admin UIs.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'For Confirmation',
            self::Confirmed => 'Confirmed',
            self::Preparing => 'Preparing',
            self::Ready => 'Ready for Release',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Statuses that permanently consume stock.
     */
    public function consumesStock(): bool
    {
        return $this !== self::Cancelled;
    }
}
