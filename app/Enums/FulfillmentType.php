<?php

namespace App\Enums;

enum FulfillmentType: string
{
    case Pickup = 'pickup';
    case Delivery = 'delivery';

    /**
     * Human readable label for storefront and admin UIs.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Branch Pick-up',
            self::Delivery => 'Delivery',
        };
    }
}
