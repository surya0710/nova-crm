<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientBelongsToOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->attributes->get('portal_organization')
            ?? $request->route('organization');

        $client = $request->user('client');

        if (! $client || ! $organization || (int) $client->organization_id !== (int) $organization->id) {
            abort(403);
        }

        if (! $client->is_active) {
            abort(403);
        }

        return $next($request);
    }
}
