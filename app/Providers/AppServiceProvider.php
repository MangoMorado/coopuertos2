<?php

namespace App\Providers;

use App\Models\Conductor;
use App\Observers\ConductorObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Registrar Observers
        Conductor::observe(ConductorObserver::class);
    }
}
