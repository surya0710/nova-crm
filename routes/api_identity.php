<?php

use App\Http\Controllers\Api\Identity\IdentityApiController;
use Illuminate\Support\Facades\Route;

Route::post('invitations/activate', [IdentityApiController::class, 'activate'])
    ->middleware('throttle:10,1')
    ->name('api.identity.invitations.activate');

Route::middleware(['auth:sanctum', 'throttle:api', 'set.organization', 'ensure.organization', 'organization.api'])
    ->prefix('identity')
    ->name('api.identity.')
    ->group(function () {
        Route::post('employees/{employee}/login-account', [IdentityApiController::class, 'createLoginAccount'])
            ->middleware('permission:hrms.manage')
            ->name('employees.login-account');

        Route::post('users/{user}/invitations', [IdentityApiController::class, 'sendInvitation'])
            ->name('users.invitations');

        Route::get('users/{user}/invitation-status', [IdentityApiController::class, 'invitationStatus'])
            ->name('users.invitation-status');

        Route::post('users/{user}/portal/enable', [IdentityApiController::class, 'enablePortal'])
            ->name('users.portal.enable');

        Route::post('users/{user}/portal/disable', [IdentityApiController::class, 'disablePortal'])
            ->name('users.portal.disable');

        Route::post('users/{user}/password-reset', [IdentityApiController::class, 'resetPassword'])
            ->name('users.password-reset');
    });
