<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Carbon;

class TrackLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $request->user()->update([
                'last_seen_at' => Carbon::now(),
            ]);
        }

        return $next($request);
    }
}
