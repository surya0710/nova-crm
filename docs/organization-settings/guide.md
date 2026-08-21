# Organization Settings Guide

## Purpose

The **Configuration Hub** is the single place for tenant configuration. Operational modules (CRM, HRMS, Recruitment, Projects) consume these settings; they do not own a separate settings system.

Configuration is shown only when all of the following are true:

1. The owning product module is **enabled** for the organization.
2. The organization's **plan** allows that module.
3. The current user has the section **permission**.

## Opening Settings

1. Open **Administration → Configuration Hub** in the sidebar.
2. Or open `/organization/settings/hub`.
3. Profile, branding, and email remain at `/organization/settings`.

## Registry

Source of truth: `config/organization_settings.php` via `App\Services\Configuration\ConfigurationRegistry`.

Each presentation module defines: key, display name, description, icon, required license (`config/modules.php` key or `null`), permission, ordered sections, and routes.

| Hub module | License | Sections |
|------------|---------|----------|
| Organization | — | Profile, Subscription, Billing, Branding, Modules, Users, Email |
| CRM | `crm` | Lead Settings, Customer Settings, Pipeline, Sales (assignment rules) |
| Commercial | `crm` | Tax / GST, Products, Price Lists, Quotations, Invoices, Payments, Automation |
| HRMS | `hrms` | Employee, Branches, Departments, Designations, Working Days, Shifts, Leave, Holidays, Attendance, WFH, Payroll; Recruitment (`recruitment` license) |
| Projects | `projects` | Categories, Types, Statuses, Templates, Task Statuses, Task Priorities |
| Marketing | `marketing` | Providers |
| Security | — | Security policies, Access Control, Audit Logs |
| Platform | — | Notifications, Workflows (`workflow` license), Custom Fields, Integrations, API, Developer |

Existing controllers, routes, and stored settings are unchanged. Hub entries deep-link to those screens (including `/organization/settings/*` aliases that redirect into HRMS catalogs).

## HR Configuration

Working days, leave policies, leave approvers, WFH policies, and attendance rules are stored on `organizations.settings` and consumed by HRMS services.

## Assets (Future Module)

The Assets module remains in the database and architecture but is hidden from navigation until the production lifecycle is complete. See `config/organization_settings.php` → `future_modules.assets`.

## Permissions

- `settings.manage` — hub entry, profile, billing, branding, commercial automation, notifications
- `organization.branches.view|manage`
- `organization.shifts.view|manage`
- `organization.hr_config.manage`
- Existing `hrms.view`, `leave.*`, `attendance.*`, `wfh.*`, `payroll.*` remain valid fallbacks
