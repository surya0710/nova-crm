# Projects Administrator Guide

## Purpose
Configure project catalogs, permissions, and dashboard provisioning for the organization.

## Required configuration
- Organizations provisioned with RBAC roles
- `config/projects.php` defaults available
- Projects module enabled in organization subscription (when subscription gating applies)

## Seeding defaults
Run the foundation seeder after migrations:

```bash
php artisan db:seed --class=ProjectFoundationSeeder
```

Individual seeders are also available:
- `ProjectCategorySeeder` — delivery categories
- `ProjectTypeSeeder` — billing/delivery types
- `ProjectStatusSeeder` — operational statuses
- `ProjectLifecycleSeeder` — lifecycle stages
- `ProjectRoleSeeder` — validates config roles map
- `ProjectPermissionSeeder` — syncs `projects.*` permissions to roles
- `ProjectWidgetSeeder` — registers system dashboard widgets
- `ProjectQuickActionSeeder` — registers system quick actions

All catalog seeders are idempotent (`updateOrCreate` per organization slug).

## Catalog administration
### Categories
Classify projects (Software Development, Implementation, Support, etc.). System categories are marked `is_system` and seeded from config.

### Types
Define delivery models (Fixed Cost, Time & Material, Sprint, etc.) with optional default duration in days.

### Statuses
Control operational states. Exactly one status should be default for new projects. Closed statuses (`is_closed`) indicate terminal states. At least one open status must remain.

### Lifecycle stages
Ordered delivery phases with sequence numbers. One stage may be marked default for new projects. Stages in use cannot be deleted.

## Permissions
| Permission | Capability |
| --- | --- |
| `projects.view` | View projects and dashboard widgets |
| `projects.create` | Create projects |
| `projects.edit` | Edit projects, milestones, lifecycle |
| `projects.manage` | Membership, archive, catalog CRUD |

`ProjectPermissionSeeder` calls `OrganizationRoleService::seedPermissions()`, clones permissions to each organization, and attaches project permissions to roles defined in `config/rbac.php` using `syncWithoutDetaching`.

## Dashboard widgets
Widget definitions live in `config/dashboard.php` (maintained by platform configuration). After updating widget config, run `ProjectWidgetSeeder` or `DashboardPlatformSeeder` to register providers:

| Widget Key | Provider |
| --- | --- |
| `my_projects` | `MyProjectsWidgetProvider` |
| `active_projects` | `ActiveProjectsWidgetProvider` |
| `project_deadlines` | `ProjectDeadlinesWidgetProvider` |
| `project_milestones` | `ProjectMilestonesWidgetProvider` |

## Metadata fields
Create published field definitions for entity type `project` to extend project forms. See [metadata-integration](metadata-integration.md).

## Dependencies
- CRM customers (optional client linkage)
- HRMS departments (optional department linkage)
- Tasks module (polymorphic task linkage)
- Metadata Platform (custom fields)
- Dashboard platform (widgets and quick actions)

## Related Documentation
See [lifecycle](lifecycle.md), [roles](roles.md), [architecture](architecture.md), and [developer-guide](developer-guide.md).
