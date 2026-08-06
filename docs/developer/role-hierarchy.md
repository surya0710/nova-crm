# Role Hierarchy

## Hierarchy Levels

Roles use `hierarchy_level` (0–100). Higher levels indicate more authority within an organization.

| Level | Typical Roles |
|-------|---------------|
| 100 | Platform Administrator |
| 90 | Organization Administrator |
| 65–70 | Department Manager, HR Manager, Finance Manager |
| 45–60 | Project Manager, Sales Manager, Team Lead, Recruiter |
| 20 | Employee (default) |
| 10 | Viewer |

## System Roles

Defined in `config/dynamic_rbac.php` under `system_roles`. Legacy slugs (`organization-owner`, `manager`, etc.) are preserved for backward compatibility.

## Default Role

Exactly one role per organization may be marked `is_default`. New members receive this role when no explicit role is specified.

## Owner Bypass

These role slugs grant all permissions within the organization:

- `organization-owner` (legacy)
- `organization-administrator`
- `platform-administrator`

## Validation Rules

- Custom roles: `is_system = false`, may be deleted/deactivated
- System roles: protected from deletion and slug changes
- Hierarchy level must be 0–100
