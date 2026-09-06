<?php

namespace App\Providers;

use App\Contracts\DeliveryQuoter;
use App\Contracts\PaymentGateway;
use App\Services\Delivery\NoDeliveryQuoter;
use App\Services\Payments\PayAtBranchGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Payment and delivery are deferred, so the deferred-safe implementations
     * are bound here. Swapping in a real provider is a one-line change.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, PayAtBranchGateway::class);
        $this->app->bind(DeliveryQuoter::class, NoDeliveryQuoter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Date::use(Carbon::class);
    }
}
