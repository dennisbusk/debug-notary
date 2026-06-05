<?php

namespace Dennisbusk\DebugNotary;

use Dennisbusk\DebugNotary\Console\TestNotaryCommand;
use Dennisbusk\DebugNotary\Http\Middleware\InjectNotaryButton;
use Dennisbusk\DebugNotary\Listeners\LogMessageListener;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class DebugNotaryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/debug-notary.php', 'debug-notary');

        $this->app->singleton('debug-notary', function ($app) {
            return new DebugNotary;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                TestNotaryCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/debug-notary.php' => config_path('debug-notary.php'),
            ], 'debug-notary-config');

            $this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/debug-notary'),
            ], 'debug-notary-lang');
        }

        if (config('debug-notary.enabled') || config('debug-notary.notary_log', true)) {
            config(['services.debug-notary' => config('debug-notary')]);

            Event::listen(
                MessageLogged::class,
                LogMessageListener::class
            );

            // Vi indlæser ikke ruter automatisk her længere,
            // da brugeren nu kan kalde DebugNotary::routes()
            // Men for bagudkompatibilitet eller bekvemmelighed kan vi lade det være valgfrit.
            // Dog er ønsket specifikt at kunne kalde ::routes().

            $this->loadViewsFrom(__DIR__.'/../resources/views', 'debug-notary');
            $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'debug-notary');
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            if (config('debug-notary.register_routes', true) && ! $this->app->routesAreCached()) {
                $this->loadRoutesFrom(__DIR__.'/routes.php');
            }

            if (config('debug-notary.enabled')) {
                $this->app->make(Kernel::class)
                    ->pushMiddleware(InjectNotaryButton::class);
            }
        }
    }
}
