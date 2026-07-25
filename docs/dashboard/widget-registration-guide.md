# Widget Registration Guide

## Config-driven registration

1. Add section (if needed) to `config/dashboard.php` → `sections`
2. Add widget entry to `config/dashboard.php` → `widgets`
3. Create provider class in `app/Services/Dashboard/Widgets/`
4. Run seeder or call `DashboardWidgetService::seedSystemWidgets()`

## Widget definition keys

| Key | Description |
|-----|-------------|
| `section` | Section slug |
| `module` | Owning module |
| `name` | Display name |
| `permission_slug` | RBAC gate (nullable) |
| `subscription_module` | Plan/module gate |
| `data_provider` | FQCN implementing contract |
| `default_width/height/position` | Default grid layout |

## Organization enablement

After registration, `DashboardProvisioningService` creates `organization_dashboard_widgets` rows filtered by plan and enabled modules.

Administrators can toggle via:

`PATCH /dashboard/widgets/{widget}/organization`

## Quick actions

Register in `config/dashboard.php` → `quick_actions` with valid named route.

Organizations customize via:

`PATCH /dashboard/quick-actions/{quickAction}/organization`
