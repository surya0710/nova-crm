<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ConfigureSubdirectory
{
    public function handle(Request $request, Closure $next): Response
    {
        $baseUrl = $this->resolveBaseUrl($request);

        if ($baseUrl === '') {
            return $next($request);
        }

        $rootUrl = rtrim($request->getSchemeAndHttpHost().$baseUrl, '/');

        URL::forceRootUrl($rootUrl);
        URL::useAssetOrigin($rootUrl);

        config([
            'app.asset_url' => $rootUrl,
            'session.path' => $baseUrl,
            'filesystems.disks.public.url' => $rootUrl.'/storage',
        ]);

        return $next($request);
    }

    protected function resolveBaseUrl(Request $request): string
    {
        return $request->getBaseUrl();
    }
}
