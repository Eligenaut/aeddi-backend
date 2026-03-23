<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        Socialite::extend('google', function ($app) {
            $config = $app['config']['services.google'];
            return new \App\Providers\GoogleStatelessProvider(
                $app['request'],
                $config['client_id'],
                $config['client_secret'],
                $config['redirect'],
            );
        });
    }
}
