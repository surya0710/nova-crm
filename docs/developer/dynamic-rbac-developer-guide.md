# Dynamic RBAC Developer Guide

## Adding a New Permission

1. Add the slug to `config/rbac.php` (or create via RBAC UI for org-specific permissions)
2. Run `php artisan migrate` (or add a sync migration)
3. Assign to roles via the permission matrix or config role definitions
4. Reference in policy: `$user->hasPermission('module.action')`
5. Add route middleware if needed: `->middleware('permission:module.action')`

## Adding a Policy Check

```php
public function viewAny(User $user): bool
{
    return $user->hasPermission('module.view');
}
```

## Managing Roles Programmatically

```php
use App\Services\Rbac\RoleService;
use App\Services\Rbac\UserRoleService;

$roleService->create($organization, [
    'name' => 'Custom Role',
    'hierarchy_level' => 40,
    'permission_ids' => [1, 2, 3],
]);

$userRoleService->assign($user, $organization, $role, $actor, primary: true);
```

## Invalidating Authorization Cache

```php
app(AuthorizationService::class)->forgetUserCache($user, $organization);
```

## Events

RBAC events are dispatched from services: `RoleCreated`, `PermissionAssigned`, `UserRoleAssigned`, `PermissionTemplateInstalled`, etc.

## Audit Logging

RBAC changes are logged via `AuditLogger` with events prefixed `rbac.*`.

## UI Routes

All RBAC management UI is under `/rbac/*` with navigation in the Access Control sidebar section.
