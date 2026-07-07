<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('platform.web')
                ->prefix('platform')
                ->name('platform.')
                ->group(base_path('routes/platform.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(prepend: [
            \App\Http\Middleware\ConfigureSubdirectory::class,
        ]);

        $middleware->group('platform.web', [
            \App\Http\Middleware\ConfigurePlatformSession::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\ConfigureSubdirectory::class,
        ]);

        $middleware->alias([
            'set.organization' => \App\Http\Middleware\SetCurrentOrganization::class,
            'ensure.organization' => \App\Http\Middleware\EnsureOrganizationIsSet::class,
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
            'platform.auth' => \App\Http\Middleware\EnsurePlatformAuthenticated::class,
            'platform.guest' => \App\Http\Middleware\RedirectIfPlatformAuthenticated::class,
            'platform.permission' => \App\Http\Middleware\EnsurePlatformPermission::class,
            'prevent.platform.tenant' => \App\Http\Middleware\PreventPlatformSessionOnTenant::class,
            'organization.lifecycle' => \App\Http\Middleware\EnsureOrganizationLifecycle::class,
            'organization.api' => \App\Http\Middleware\EnsureOrganizationApiAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
