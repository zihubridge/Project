<?php

namespace App\Providers;

use App\Services\Providers\NearIntents\NearIntentsHttpClient;
use App\Services\Providers\NearIntents\NearIntentsProvider;
use App\Services\Providers\SwapProviderRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(NearIntentsHttpClient::class, fn () => new NearIntentsHttpClient());

        $this->app->singleton(NearIntentsProvider::class, function ($app) {
            return new NearIntentsProvider($app->make(NearIntentsHttpClient::class));
        });

        $this->app->singleton(SwapProviderRegistry::class, function ($app) {
            $registry = new SwapProviderRegistry();
            $registry->register($app->make(NearIntentsProvider::class));

            return $registry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        RateLimiter::for('swap-catalog', function (Request $request) {
            return Limit::perMinute(90)->by($request->ip());
        });

        RateLimiter::for('swap-quotes', function (Request $request) {
            return Limit::perMinute(45)->by($request->ip());
        });

        RateLimiter::for('swap-orders', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('contact-form', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
