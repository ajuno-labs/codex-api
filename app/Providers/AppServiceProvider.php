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

        // Auto-generate IDE helper files in development
        if ($this->app->environment('local')) {
            try {
                $this->app->make('Illuminate\Contracts\Console\Kernel')->call('ide-helper:generate');
                $this->app->make('Illuminate\Contracts\Console\Kernel')->call('ide-helper:models', ['--nowrite' => true]);
            } catch (\Exception $e) {
                // Silently fail if ide-helper commands are not available
                // This prevents errors if the package isn't installed yet
            }
        }
    }
}
