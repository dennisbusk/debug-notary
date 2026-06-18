<?php

namespace Dennisbusk\DebugNotary\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InjectNotaryButton
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Quick checks based on the request alone
        if (! config('debug-notary.enabled')
            || ! config('debug-notary.notary_log', true)
            || $request->ajax()
            || $request->isXmlHttpRequest()
            || $request->wantsJson()
            || $request->hasHeader('X-Livewire')
            || str_contains($request->getPathInfo(), '/livewire')
        ) {
            return $next($request);
        }

        $response = $next($request);

        // Check access via Gate if configured
        if ($gate = config('debug-notary.access_gate')) {
            try {
                $userId = auth()->id() ?: 'guest';
                $hasAccess = cache()->remember("debug-notary-access-{$userId}", 3600, function () use ($gate) {
                    return Gate::allows($gate);
                });

                if (! $hasAccess) {
                    return $response;
                }
            } catch (\Exception $e) {
                // If gate doesn't exist or fails, we don't show it just to be safe
                return $response;
            }
        }

        // Check if it's a Response instance and if it's HTML
        if (! method_exists($response, 'getContent') || ! str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            return $response;
        }

        $content = $response->getContent();

        if (! is_string($content)) {
            return $response;
        }

        try {
            $view = view('debug-notary::components.notary-button')->render();
        } catch (\Exception $e) {
            return $response;
        }

        // Find position before </body>
        $pos = strripos($content, '</body>');

        if ($pos !== false) {
            $newContent = substr($content, 0, $pos).$view.substr($content, $pos);
        } else {
            $newContent = $content.$view;
        }

        $response->setContent($newContent);

        return $response;
    }
}
