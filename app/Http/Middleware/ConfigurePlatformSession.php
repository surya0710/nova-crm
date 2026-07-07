<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigurePlatformSession
{
    public function handle(Request $request, Closure $next): Response
    {
        config([
            'session.cookie' => config('platform.session_cookie'),
        ]);

        return $next($request);
    }
}
