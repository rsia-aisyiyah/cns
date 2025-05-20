<?php

namespace App\Providers;

use App\Services\AesHasher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        
        Auth::provider('aes.user.provider', function ($app, array $config) {
            return new AesUserProvider(new AesHasher(), $config['model']);
        });
    }
}
