<?php

use App\Http\Controllers\Rbac\AuthorizationLookupController;
use App\Http\Controllers\Rbac\PermissionController;
use App\Http\Controllers\Rbac\PermissionGroupController;
use App\Http\Controllers\Rbac\PermissionTemplateController;
use App\Http\Controllers\Rbac\RoleController;
use App\Http\Controllers\Rbac\RolePermissionController;
use App\Http\Controllers\Rbac\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['permission:rbac.view'])->prefix('rbac')->name('rbac.')->group(function () {
    Route::get('authorization', AuthorizationLookupController::class)->name('authorization.lookup');

    Route::resource('permission-groups', PermissionGroupController::class)->except(['show', 'destroy']);
    Route::post('permission-groups/{permission_group}/archive', [PermissionGroupController::class, 'archive'])->name('permission-groups.archive');

    Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::patch('permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
    Route::post('permissions/{permission}/activate', [PermissionController::class, 'activate'])->name('permissions.activate');
    Route::post('permissions/{permission}/deactivate', [PermissionController::class, 'deactivate'])->name('permissions.deactivate');

    Route::resource('roles', RoleController::class)->except(['show']);
    Route::post('roles/{role}/duplicate', [RoleController::class, 'duplicate'])->name('roles.duplicate');
    Route::post('roles/{role}/activate', [RoleController::class, 'activate'])->name('roles.activate');
    Route::post('roles/{role}/deactivate', [RoleController::class, 'deactivate'])->name('roles.deactivate');

    Route::get('matrix', [RolePermissionController::class, 'matrix'])->name('matrix.index');
    Route::post('matrix/bulk', [RolePermissionController::class, 'bulkUpdate'])->name('matrix.bulk')->middleware('permission:rbac.permissions.manage');
    Route::post('roles/{role}/permissions', [RolePermissionController::class, 'sync'])->name('roles.permissions.sync')->middleware('permission:rbac.permissions.manage');

    Route::get('user-roles', [UserRoleController::class, 'index'])->name('user-roles.index');
    Route::get('user-roles/{user}', [UserRoleController::class, 'show'])->name('user-roles.show');
    Route::post('user-roles/{user}', [UserRoleController::class, 'sync'])->name('user-roles.sync')->middleware('permission:rbac.roles.manage');

    Route::get('templates', [PermissionTemplateController::class, 'index'])->name('templates.index');
    Route::get('templates/{template}', [PermissionTemplateController::class, 'show'])->name('templates.show');
    Route::post('templates/install', [PermissionTemplateController::class, 'install'])->name('templates.install')->middleware('permission:rbac.templates.manage');
    Route::post('templates/reset', [PermissionTemplateController::class, 'reset'])->name('templates.reset')->middleware('permission:rbac.templates.manage');
});
