<?php

namespace App\Providers;

use App\Models\PlatformUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PlatformServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (config('platform.permissions', []) as $permission => $description) {
            Gate::define($permission, function (PlatformUser $user) use ($permission) {
                return $user->hasPermission($permission);
            });
        }
    }
}
