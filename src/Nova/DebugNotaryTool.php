<?php

namespace Dennisbusk\DebugNotary\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class DebugNotaryTool extends Tool
{
    /**
     * Perform any tasks that need to happen when the tool is booted.
     */
    public function boot(): void
    {
        Nova::mix('debug-notary-tool', __DIR__.'/../../dist/mix-manifest.json');

        Nova::provideToScript([
            'debugNotaryPrefix' => config('debug-notary.route_prefix', config('debug-notary.prefix', 'laravel-debug-notary')),
        ]);
    }

    /**
     * Build the menu that renders the navigation links for the tool.
     */
    public function menu(Request $request): MenuSection
    {
        return MenuSection::make(__('Debug Notary'))
            ->path('/debug-notary')
            ->icon('bug-ant');
    }
}
