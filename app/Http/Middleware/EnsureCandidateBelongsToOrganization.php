<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCandidateBelongsToOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->attributes->get('career_organization');
        $account = $request->user('candidate');

        if (! $account || ! $organization || (int) $account->organization_id !== (int) $organization->id) {
            abort(403);
        }

        return $next($request);
    }
}
