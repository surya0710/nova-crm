# Organization Provisioning (RBAC)

## Automatic Provisioning

The `Organization` model `created` event triggers `OrganizationRoleService::seedDefaultRoles()`, which delegates to `OrganizationProvisioningService::provision()`.

## Provisioning Steps

1. Seed global permissions (`OrganizationRoleService::seedPermissions`)
2. Clone permissions for the organization (`PermissionService::cloneForOrganization`)
3. Install default permission template (`PermissionTemplateService::applyDefault`)
4. Ensure legacy config roles exist (`ensureLegacyRoles`)
5. Assign Organization Administrator to owner when provided

## Platform-Created Organizations

`OrganizationManagementService` creates organizations via the platform admin. The same `Organization::created` hook applies.

## Manual Re-Provisioning

```bash
php artisan db:seed --class=DefaultOrganizationRoleSeeder
```

Or install a specific template from **Settings → Access Control → Templates**.

## Owner Assignment

The first user attached as `organization-owner` receives the Organization Administrator role with full permissions. The legacy `organization_user.role_id` pivot remains the primary role; additional roles are stored in `user_roles`.
