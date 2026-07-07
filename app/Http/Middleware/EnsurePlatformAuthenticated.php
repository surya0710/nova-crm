<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('platform')->check()) {
            return redirect()->route('platform.login');
        }

        $user = Auth::guard('platform')->user();

        if (! $user->isActive()) {
            Auth::guard('platform')->logout();
            $request->session()->invalidate();

            return redirect()->route('platform.login')
                ->withErrors(['email' => __('Your platform account is inactive.')]);
        }

        return $next($request);
    }
}
