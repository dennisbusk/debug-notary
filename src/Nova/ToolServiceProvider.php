<?php

namespace Dennisbusk\DebugNotary\Nova;

use Dennisbusk\DebugNotary\Nova\Http\Middleware\Authorize;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Nova;

class ToolServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! class_exists(Nova::class)) {
            return;
        }

        $this->app->booted(function () {
            $this->routes();
        });
    }

    /**
     * Register the tool's routes.
     */
    protected function routes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Nova::router(['nova', 'nova.auth', Authorize::class], 'debug-notary')
            ->group(__DIR__.'/../../routes/nova-inertia.php');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
