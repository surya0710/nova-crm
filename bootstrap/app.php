<?php

use App\Http\Middleware\ConfigurePlatformSession;
use App\Http\Middleware\ConfigureSubdirectory;
use App\Http\Middleware\EnsureOrganizationApiAccess;
use App\Http\Middleware\EnsureOrganizationIsSet;
use App\Http\Middleware\EnsureOrganizationLifecycle;
use App\Http\Middleware\EnsurePlatformAuthenticated;
use App\Http\Middleware\EnsurePlatformPermission;
use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\MarketingTrackingMiddleware;
use App\Http\Middleware\PreventPlatformSessionOnTenant;
use App\Http\Middleware\RedirectIfPlatformAuthenticated;
use App\Http\Middleware\SetCurrentOrganization;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('platform.web')
                ->prefix('platform')
                ->name('platform.')
                ->group(base_path('routes/platform.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(prepend: [
            ConfigureSubdirectory::class,
        ]);

        $middleware->group('platform.web', [
            ConfigurePlatformSession::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            SubstituteBindings::class,
            ConfigureSubdirectory::class,
        ]);

        $middleware->alias([
            'set.organization' => SetCurrentOrganization::class,
            'ensure.organization' => EnsureOrganizationIsSet::class,
            'permission' => EnsureUserHasPermission::class,
            'platform.auth' => EnsurePlatformAuthenticated::class,
            'platform.guest' => RedirectIfPlatformAuthenticated::class,
            'platform.permission' => EnsurePlatformPermission::class,
            'prevent.platform.tenant' => PreventPlatformSessionOnTenant::class,
            'organization.lifecycle' => EnsureOrganizationLifecycle::class,
            'organization.api' => EnsureOrganizationApiAccess::class,
            'marketing.tracking' => MarketingTrackingMiddleware::class,
        ]);

        // Beacon-style endpoint hit by anonymous browsers that hold no CSRF
        // token; protected instead by throttling and strict payload validation.
        // Provider webhooks are signed (e.g. Meta X-Hub-Signature-256) and
        // similarly cannot carry a Laravel CSRF cookie.
        $middleware->validateCsrfTokens(except: [
            'marketing/track',
            'webhooks/marketing/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
