<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureUserIsAdmin
{
    /**
     * Réserve le back-office aux comptes du personnel (vendeur, gestionnaire
     * de stock, administrateur). L'accès à chaque zone précise est ensuite
     * affiné route par route par le middleware `permission:xxx`.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isStaff()) {
            throw new AccessDeniedHttpException('Espace réservé au personnel.');
        }

        return $next($request);
    }
}
