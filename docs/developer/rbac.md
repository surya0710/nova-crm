# RBAC

Konnect Nex uses a dynamic, organization-scoped Role Based Access Control (RBAC) platform.

## Documentation

- [Dynamic RBAC Architecture](dynamic-rbac-architecture.md)
- [Permission Naming Standards](permission-naming-standards.md)
- [Role Hierarchy](role-hierarchy.md)
- [Permission Templates](permission-templates.md)
- [Seeder Strategy](rbac-seeder-strategy.md)
- [Authorization Flow](authorization-flow.md)
- [Organization Provisioning](organization-provisioning-rbac.md)
- [Developer Guide](dynamic-rbac-developer-guide.md)
- [User Guide](../crm/user-guide/access-control.md)

## Quick Reference

- Permissions are evaluated via `AuthorizationService` (cached, organization-scoped).
- Policies call `$user->hasPermission()` which delegates to the authorization service.
- Organization owners bypass permission checks.
- Manage roles and permissions at **Settings → Access Control** (`/rbac/roles`).
- CRM tickets, contacts, and sales activities reuse `customers.*` (no extra slugs). Opportunity enhancements reuse `opportunities.*`. REST CRM APIs also require `api.access`. Cross-tenant IDs return 404.

## Verification

Test allowed and denied paths for each sensitive action. See `tests/Feature/DynamicRbacTest.php` and `tests/Feature/RbacTest.php`.
