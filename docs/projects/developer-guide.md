# Projects Developer Guide

## Purpose
Guide for extending the Project Foundation module with services, events, permissions, and dashboard widgets.

## Key paths
| Area | Path |
| --- | --- |
| Config | `config/projects.php` |
| Models | `app/Models/Project*.php` |
| Services | `app/Services/Project*.php` |
| Events | `app/Events/Project*.php` |
| Widgets | `app/Services/Dashboard/Widgets/*Projects*WidgetProvider.php` |
| Seeders | `database/seeders/Project*Seeder.php` |
| Migration | `database/migrations/2026_07_22_000010_create_project_foundation_tables.php` |

## Service extension
Prefer extending existing services over duplicating business rules:
- `ProjectService` — core project lifecycle, archive guards, metadata persistence
- `ProjectMemberService` — membership with role validation against `config('projects.roles')`
- `ProjectLifecycleService` — stage transitions with workflow causation metadata
- `ProjectDefaultsService` — idempotent catalog seeding

Set `TenantContext` before tenant-scoped queries in controllers, jobs, and widget providers.

## Events
Subscribe workflow listeners or domain reactions to:
- `ProjectCreated`, `ProjectUpdated`, `ProjectArchived`, `ProjectRestored`
- `ProjectLifecycleChanged`
- `ProjectMemberAssigned`, `ProjectMemberRemoved`
- `ProjectMilestoneCreated`, `ProjectMilestoneCompleted`

Events use the platform `forModel()` factory pattern and include actor context where applicable.

## Permissions
Add new permission slugs to `config/rbac.php` (platform config) and re-run `ProjectPermissionSeeder`. The seeder:
1. Seeds system permissions via `OrganizationRoleService::seedPermissions()`
2. Clones to organizations via `PermissionService::cloneForOrganization()`
3. Attaches `projects.*` slugs to roles from `config/rbac.roles` without detaching existing grants

## Dashboard widgets
Extend `AbstractWidgetProvider`:
```php
class ExampleWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string { return 'example'; }
    public function subscriptionModule(): ?string { return 'projects'; }
    public function permissionSlug(): ?string { return 'projects.view'; }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);
        return ['count' => Project::query()->count()];
    }
}
```

Register in `config/dashboard.php` and run `ProjectWidgetSeeder`.

## Metadata
Use entity key `project` when defining field definitions. Persist through `MetadataEntityFormService` rather than writing JSON directly.

## Testing recommendations
- Catalog seeding idempotency per organization
- Archive read-only enforcement
- Widget authorization (permission + subscription)
- Lifecycle stage change events
- Cross-tenant isolation (404 on foreign IDs)

## Related Documentation
See [architecture](architecture.md), [apis](apis.md), [metadata-integration](metadata-integration.md), and [dashboard/widget-development-guide.md](../dashboard/widget-development-guide.md).
