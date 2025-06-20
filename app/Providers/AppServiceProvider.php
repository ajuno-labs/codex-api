<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;

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
        $socialite = $this->app->make(Factory::class);

        // Register Google provider
        $socialite->extend('google', function ($app) use ($socialite) {
            $config = $app['config']['services.google'];
            return $socialite->buildProvider(\SocialiteProviders\Google\Provider::class, $config);
        });

        // Register GitHub provider
        $socialite->extend('github', function ($app) use ($socialite) {
            $config = $app['config']['services.github'];
            return $socialite->buildProvider(\SocialiteProviders\GitHub\Provider::class, $config);
        });
    }
}
