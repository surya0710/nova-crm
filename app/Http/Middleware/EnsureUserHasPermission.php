<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function __construct(protected TenantContext $tenant) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        $organization = $this->tenant->get();

        if (! $user) {
            abort(403);
        }

        if ($user->is_super_admin || ($organization && $user->isOwnerOf($organization))) {
            return $next($request);
        }

        if (! $user->hasPermission($permission, $organization)) {
            abort(403);
        }

        return $next($request);
    }
}
