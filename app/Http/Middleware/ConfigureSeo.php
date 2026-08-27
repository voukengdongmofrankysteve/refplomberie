<?php

namespace App\Http\Middleware;

use App\Support\Seo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigureSeo
{
    /**
     * Seules les pages de la vitrine ont vocation à être indexées.
     * Le back-office, l'espace client et les écrans d'authentification sont
     * explicitement sortis des index.
     *
     * @var array<int, string>
     */
    private const INDEXABLE = [
        '/',
        'produit/*',
    ];

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is(...self::INDEXABLE)) {
            app(Seo::class)->noindex();
        }

        return $next($request);
    }
}
