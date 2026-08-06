# Dashboard Architecture

Phase 11.8 introduces a widget-based Dashboard & Workspace Platform for Konnect Nex.

## Layers

```
Dashboard
  └── Dashboard Sections
        └── Dashboard Widgets
              └── Widget Data Providers
                    └── Module Services
```

## Core components

| Component | Responsibility |
|-----------|----------------|
| `DashboardService` | Builds dashboard layout with org + user preferences |
| `DashboardWidgetService` | Registers, enables/disables, validates widgets |
| `DashboardPreferenceService` | Personal layout save/reset/hide/restore |
| `QuickActionService` | Module shortcuts with RBAC + subscription checks |
| `WidgetDataService` | Lazy data loading, refresh, caching |
| `WorkspaceService` | Full workspace: dashboard, quick actions, notifications, activities |
| `ModuleSubscriptionService` | Plan + enabled module validation |
| `DashboardProvisioningService` | Org onboarding defaults |

## Rendering rules

Widgets and quick actions appear only when:

1. Module is enabled for the organization
2. Organization plan allows the module
3. Widget/action is enabled for the organization
4. User has required permission
5. Widget/action is active

## Persistence

- System definitions: `dashboard_sections`, `dashboard_widgets`, `dashboard_quick_actions`
- Organization overrides: `organization_dashboard_widgets`, `organization_quick_actions`
- User layout: `user_dashboard_preferences`

## Caching

Version-bump cache invalidation via `DashboardCache` (organization-scoped).

## Events

`DashboardCreated`, `WidgetRegistered`, `WidgetEnabled`, `WidgetDisabled`, `WidgetMoved`, `WidgetResized`, `DashboardReset`, `QuickActionCreated`, `QuickActionUpdated`, `WorkspaceLoaded`

## Provisioning

`OrganizationProvisioningService` calls `DashboardProvisioningService::provision()` for every new organization.
