# Dashboard Administrator Guide

## Permissions

| Permission | Purpose |
|------------|---------|
| `dashboard.view` | Access workspace and widgets |
| `dashboard.customize` | Personal layout changes |
| `dashboard.manage` | Organization widget/action configuration |

## Organization setup

New organizations automatically receive default widgets and quick actions via provisioning.

## Plan modules

Configure in `config/dashboard.php` → `plan_modules`:

- `starter` — CRM + common modules
- `professional` — CRM, HRMS, recruitment, finance, etc.
- `enterprise` — all modules

## Per-organization module toggles

Set `settings.enabled_modules` on the organization to restrict modules within the plan.

## Managing widgets

Disable a widget for all users in the org:

```http
PATCH /dashboard/widgets/{id}/organization
{ "is_enabled": false }
```

## Managing quick actions

```http
PATCH /dashboard/quick-actions/{id}/organization
{ "is_enabled": false, "sort_order": 10 }
```

System quick actions cannot be deleted; they can only be disabled or reordered.

## Re-seeding

```bash
php artisan db:seed --class=DashboardPlatformSeeder
```

This refreshes system definitions and reprovisions all organizations.

## Audit trail

Widget and quick action changes are recorded via `AuditLogger` on organization override records.
