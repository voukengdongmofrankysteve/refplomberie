<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsurePermission
{
    /**
     * Affine le gate `admin` (personnel authentifié) route par route, selon
     * la zone du back-office que le rôle du compte autorise.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        $required = Permission::from($permission);

        if ($user === null || ! $user->hasPermission($required)) {
            throw new AccessDeniedHttpException('Accès non autorisé pour ce rôle.');
        }

        return $next($request);
    }
}
