<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        if (config('filament-shield.super_admin.enabled')) {
            Gate::before(function ($user, $ability) {
                return $user->hasRole(config('filament-shield.super_admin.name')) ? true : null;
            });
        }

    }
}
