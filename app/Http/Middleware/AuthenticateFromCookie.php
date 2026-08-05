<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFromCookie
{
    /**
     * Convertit le cookie httpOnly "auth_token" en en-tête Authorization
     * (Bearer) afin que le guard "sanctum" puisse authentifier la requête
     * sans que le front-end n'ait besoin de lire ou stocker le token.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->headers->has('Authorization')) {
            $token = $request->cookie('auth_token');

            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        return $next($request);
    }
}
