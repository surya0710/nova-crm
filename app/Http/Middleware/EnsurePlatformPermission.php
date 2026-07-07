<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::guard('platform')->user();

        if (! $user || ! $user->hasPermission($permission)) {
            abort(403, 'Unauthorized platform action.');
        }

        return $next($request);
    }
}
