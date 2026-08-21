<?php

use App\Exceptions\MissingEmployeeRecordException;
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
use App\Http\Middleware\RecordRecentPage;
use App\Http\Middleware\RedirectIfPlatformAuthenticated;
use App\Http\Middleware\SetCurrentOrganization;
use App\Support\Api\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

            Route::middleware('web')
                ->group(base_path('routes/careers.php'));

            Route::middleware('web')
                ->group(base_path('routes/portal.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(prepend: [
            ConfigureSubdirectory::class,
        ]);

        $middleware->web(append: [
            RecordRecentPage::class,
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
            'module' => \App\Http\Middleware\EnsureOrganizationHasModule::class,
            'marketing.tracking' => MarketingTrackingMiddleware::class,
            'careers.organization' => \App\Http\Middleware\ResolveCareerOrganization::class,
            'careers.candidate' => \App\Http\Middleware\EnsureCandidateBelongsToOrganization::class,
            'portal.organization' => \App\Http\Middleware\ResolvePortalOrganization::class,
            'portal.client' => \App\Http\Middleware\EnsureClientBelongsToOrganization::class,
        ]);

        // Beacon-style endpoint hit by anonymous browsers that hold no CSRF
        // token; protected instead by throttling and strict payload validation.
        // Provider webhooks are signed (e.g. Meta X-Hub-Signature-256) and
        // similarly cannot carry a Laravel CSRF cookie.
        $middleware->validateCsrfTokens(except: [
            'marketing/track',
            'webhooks/marketing/*',
            'webhooks/email/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $isApi = static function (Request $request): bool {
            return $request->is('api/*') || $request->expectsJson();
        };

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(
                $e->getMessage() ?: __('The given data was invalid.'),
                422,
                $e->errors(),
            );
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error($e->getMessage() ?: __('Unauthenticated.'), 401);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error($e->getMessage() ?: __('This action is unauthorized.'), 403);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(__('Resource not found.'), 404);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error($e->getMessage() ?: __('Not found.'), 404);
        });

        $exceptions->render(function (MissingEmployeeRecordException $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            return ApiResponse::success([
                'empty_state' => true,
                'audience' => $e->audience,
            ], $e->getMessage());
        });
    })->create();
