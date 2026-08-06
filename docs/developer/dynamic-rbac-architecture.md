# Dynamic RBAC Architecture

## Overview

Konnect Nex Phase 11.7 introduces organization-scoped, dynamic Role Based Access Control (RBAC). Permissions, roles, groups, and templates are stored in the database and provisioned per organization.

## Core Tables

| Table | Scope | Purpose |
|-------|-------|---------|
| `permission_groups` | System + org | Logical grouping of permissions |
| `permissions` | System + org | Atomic permission definitions |
| `roles` | Organization | Named roles with hierarchy |
| `role_permissions` | Organization | Role ↔ permission assignments |
| `user_roles` | Organization | Additional roles per user |
| `permission_templates` | Platform | Predefined RBAC templates |
| `permission_template_roles` | Platform | Roles within a template |
| `permission_template_permissions` | Platform | Permissions per template role |

## Service Layer

- `PermissionGroupService` — group CRUD and activation
- `PermissionService` — permission CRUD, lookup, org cloning
- `RoleService` — role CRUD, clone, hierarchy validation
- `RolePermissionService` — matrix sync and bulk updates
- `UserRoleService` — multi-role assignment, effective permissions
- `AuthorizationService` — cached permission evaluation
- `PermissionTemplateService` — template install/reset/clone
- `OrganizationProvisioningService` — new org bootstrap

## Authorization Flow

1. Request enters tenant middleware (`SetCurrentOrganization`)
2. Route middleware or policy calls `AuthorizationService::can()`
3. Service resolves primary role (`organization_user.role_id`) + `user_roles`
4. Owner/administrator roles bypass checks
5. Effective permission slugs are cached per user/org (5 minutes)

Policies continue to call `$user->hasPermission()` which delegates to `AuthorizationService`.

## Organization Provisioning

When an organization is created:

1. Global permissions are ensured in the catalog
2. Organization permission clones are created
3. Default permission template (Corporate) is installed
4. Legacy config roles are synced for backward compatibility
5. Organization Administrator is assigned to the owner when provided

## Multi-Tenancy

All role and assignment tables include `organization_id`. Permission lookups always filter by organization context. Cross-tenant access is prevented at the service and policy layers.
