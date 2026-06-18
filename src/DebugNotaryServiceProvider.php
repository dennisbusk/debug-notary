<?php

namespace Dennisbusk\DebugNotary;

use Dennisbusk\DebugNotary\Console\TestNotaryCommand;
use Dennisbusk\DebugNotary\Http\Livewire\BugBulkActions;
use Dennisbusk\DebugNotary\Http\Livewire\BugDetail;
use Dennisbusk\DebugNotary\Http\Livewire\BugRow;
use Dennisbusk\DebugNotary\Http\Livewire\BugTable;
use Dennisbusk\DebugNotary\Http\Middleware\InjectNotaryButton;
use Dennisbusk\DebugNotary\Listeners\LogMessageListener;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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

        if (class_exists(AliasLoader::class)) {
            AliasLoader::getInstance()->alias('DebugNotary', Facades\DebugNotary::class);
        }
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

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/debug-notary'),
            ], 'debug-notary-views');
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

            $this->app->booted(function () {
                if (config('debug-notary.register_routes', true)
                    &&
                    ! DebugNotary::$routesRegistered
                    &&
                    ! Route::has('debug-notary.index')
                    &&
                    ! $this->app->routesAreCached()) {
                    $this->loadRoutesFrom(__DIR__.'/routes.php');
                }
            });

            if (config('debug-notary.enabled')) {
                $this->app->make(Kernel::class)
                    ->pushMiddleware(InjectNotaryButton::class);
            }

            Livewire::component('bug-table', BugTable::class);
            Livewire::component('bug-row', BugRow::class);
            Livewire::component('bug-detail', BugDetail::class);
            Livewire::component('notary-bulk-actions', BugBulkActions::class);
        }
    }
}
