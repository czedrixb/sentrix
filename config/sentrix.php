<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Order Numbers
    |--------------------------------------------------------------------------
    |
    | Prefix applied to generated order numbers. Order numbers are unique and
    | sequential per day rather than random, so they cannot collide.
    |
    */

    'order_number_prefix' => env('SENTRIX_ORDER_PREFIX', 'SNX'),

    /*
    |--------------------------------------------------------------------------
    | Cart
    |--------------------------------------------------------------------------
    |
    | How long a guest cart survives, and the cookie the SPA carries to identify
    | it. Carts are persisted rather than held in the session so the API can
    | stay stateless.
    |
    */

    'cart' => [
        'cookie' => 'sentrix_cart',
        'lifetime_days' => (int) env('SENTRIX_CART_LIFETIME_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduling
    |--------------------------------------------------------------------------
    |
    | Bounds for the pick-up / delivery slot a customer may choose at checkout.
    |
    */

    'scheduling' => [
        'opens_at' => env('SENTRIX_SCHEDULE_OPENS_AT', '08:00'),
        'closes_at' => env('SENTRIX_SCHEDULE_CLOSES_AT', '17:00'),
        'slot_minutes' => (int) env('SENTRIX_SCHEDULE_SLOT_MINUTES', 30),
        'max_days_ahead' => (int) env('SENTRIX_SCHEDULE_MAX_DAYS_AHEAD', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeding
    |--------------------------------------------------------------------------
    |
    | Password given to seeded staff accounts. Never hardcode a credential in a
    | seeder; supply this through the environment instead.
    |
    */

    'seed_password' => env('SEED_ADMIN_PASSWORD', 'password'),

];
