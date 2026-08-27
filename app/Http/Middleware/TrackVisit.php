<?php

namespace App\Http\Middleware;

use App\Enums\AnalyticsEvent;
use App\Facades\Analytics;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compte une page vue pour chaque affichage réel.
 *
 * Posé après la réponse : une page qui a fini en erreur 500 ou en redirection
 * n'a rien montré à personne, et n'a donc pas à compter dans l'audience.
 */
class TrackVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->countable($request, $response)) {
            Analytics::record(
                AnalyticsEvent::PageView,
                path: '/'.ltrim($request->path(), '/'),
            );
        }

        return $response;
    }

    private function countable(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || ! $response->isSuccessful()) {
            return false;
        }

        // Le back-office n'est pas de l'audience : c'est nous.
        if ($request->is(...config('analytics.ignore', []))) {
            return false;
        }

        // Les téléchargements, flux et images ne sont pas des pages.
        $type = (string) $response->headers->get('Content-Type', '');

        $isPage = str_contains($type, 'text/html')
            || $request->header('X-Inertia') !== null;

        return $isPage;
    }
}
