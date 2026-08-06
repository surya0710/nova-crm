# RBAC Seeder Strategy

## Seeder Order

`DynamicRbacSeeder` runs seeders in this order:

1. `PermissionGroupSeeder` — 22 system groups
2. `PermissionSeeder` — global permission catalog
3. `RoleTemplateSeeder` — placeholder (roles defined in config)
4. `PermissionTemplateSeeder` — 5 platform templates
5. `PermissionTemplateRoleSeeder` — roles per template
6. `PermissionTemplatePermissionSeeder` — permission slugs per template role
7. `DefaultOrganizationPermissionSeeder` — clone permissions per org
8. `DefaultOrganizationRoleSeeder` — provision roles via template
9. `DefaultOrganizationRolePermissionSeeder` — sync legacy config role permissions

## Running Seeders

```bash
php artisan db:seed --class=DynamicRbacSeeder
```

The migration `2026_07_22_000002_sync_dynamic_rbac_permissions.php` invokes `DynamicRbacSeeder` for existing installations.

## Configuration Sources

- `config/rbac.php` — legacy permission and role definitions
- `config/dynamic_rbac.php` — groups, system roles, templates, RBAC admin permissions

## Test Environment

Feature tests use `RefreshDatabase` which runs all migrations including RBAC sync. No destructive commands against development databases.
