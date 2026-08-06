# Authorization Flow

## Entry Points

| Layer | Mechanism |
|-------|-----------|
| Routes | `permission:{slug}` middleware |
| Policies | `$user->hasPermission('{slug}', $org)` |
| Blade | `$can('{slug}')` or `$user->hasPermission()` |
| Gate | `Gate::define('permission', ...)` in AppServiceProvider |
| API | Sanctum + org middleware + permission middleware |

## AuthorizationService

Central service at `App\Services\Rbac\AuthorizationService`:

```php
$authorization->can($user, 'leads.view', $organization);
$authorization->canAny($user, ['leads.view', 'leads.manage'], $organization);
$authorization->effectivePermissions($user, $organization);
```

## Super Admin

Users with `is_super_admin = true` bypass all tenant permission checks.

## Caching

Effective permissions are cached for 300 seconds per user/organization. Cache is invalidated on role assignment changes via `forgetUserCache()` / `forgetOrganizationCache()`.

## Policy Integration

Policies should use `$user->hasPermission()` (which delegates to `AuthorizationService`). Do not read static config for permission checks in application code.

## API Lookup

`GET /rbac/authorization` (web) or `GET /api/v1/rbac/authorization` returns effective permissions or a single permission check.
