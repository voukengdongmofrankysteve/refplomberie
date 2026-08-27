<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fait de Sanctum le garde par défaut de toute l'API.
 *
 * Sans cela, `$request->user()` interroge le garde « web » et renvoie `null`
 * sur les routes ouvertes aux visiteurs — même quand un jeton valide est
 * présent. Une commande passée depuis l'application ne serait alors rattachée
 * à aucun compte, et n'apparaîtrait jamais dans « mes commandes ».
 *
 * Les routes qui exigent une authentification gardent `auth:sanctum` : ce
 * middleware identifie, il n'autorise pas.
 */
class UseSanctumGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('sanctum');

        return $next($request);
    }
}
