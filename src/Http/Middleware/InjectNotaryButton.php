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
        $response = $next($request);

        // Knappen skal altid vises hvis notary_log er true
        if (! config('debug-notary.notary_log', true)) {
            return $response;
        }

        // Tjek adgang via Gate hvis konfigureret
        if ($gate = config('debug-notary.access_gate')) {
            try {
                if (Gate::denies($gate)) {
                    return $response;
                }
            } catch (\Exception $e) {
                // Hvis gate ikke findes eller fejler, viser vi den ikke for en sikkerheds skyld
                return $response;
            }
        }

        // Undgå injicering i AJAX, JSON eller ikke-HTML responser
        if ($request->ajax() || $request->isXmlHttpRequest() || $request->wantsJson()) {
            return $response;
        }

        // Vi tjekker om det er en Response instans og om det er HTML
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

        // Find positionen før </body>
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
