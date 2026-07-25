<?php

use App\Http\Controllers\Rbac\AuthorizationLookupController;
use App\Http\Controllers\Rbac\PermissionController;
use App\Http\Controllers\Rbac\PermissionGroupController;
use App\Http\Controllers\Rbac\PermissionTemplateController;
use App\Http\Controllers\Rbac\RoleController;
use App\Http\Controllers\Rbac\RolePermissionController;
use App\Http\Controllers\Rbac\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/rbac')->middleware(['auth:sanctum', 'set.organization', 'ensure.organization', 'organization.api'])->group(function () {
    Route::get('authorization', AuthorizationLookupController::class);

    Route::get('permission-groups', [PermissionGroupController::class, 'index']);
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::get('roles', [RoleController::class, 'index']);
    Route::get('matrix', [RolePermissionController::class, 'matrix']);
    Route::get('user-roles', [UserRoleController::class, 'index']);
    Route::get('user-roles/{user}', [UserRoleController::class, 'show']);
    Route::get('templates', [PermissionTemplateController::class, 'index']);
    Route::get('templates/{template}', [PermissionTemplateController::class, 'show']);
});
