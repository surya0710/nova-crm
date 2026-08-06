<?php

namespace App\Events\Rbac;

use App\Models\Permission;
use App\Models\PermissionTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class PermissionRevoked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Role $role,
        public readonly Permission $permission,
        public readonly ?User $actor = null,
    ) {}
}
