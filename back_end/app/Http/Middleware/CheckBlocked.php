<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->is_blocked) {
            // Si la requête attend du JSON (API), on retourne une erreur 403 spécifique
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Votre compte est bloqué.', 'is_blocked' => true], 403);
            }
        }

        return $next($request);
    }
}
