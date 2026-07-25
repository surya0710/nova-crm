# Organization Settings Guide

## Purpose

Organization Settings is the single place for tenant configuration. Operational modules (HRMS, CRM, Recruitment) consume these settings; they no longer own configuration pages in the primary sidebar.

## Opening Settings

1. Open **Settings → Organization Settings** in the sidebar.
2. Or open `/organization/settings/hub`.
3. Profile/branding/email remain at `/organization/settings`.

## Structure

| Group | Sections |
|-------|----------|
| Organization | Profile, Subscription, Branding, Billing |
| Structure | Branches, Departments, Designations |
| HR Configuration | Working Days, Shift Management, Holiday Calendar, Leave Types, Leave Policies, Leave Approvers, Attendance Rules |
| Platform | Access Control, Dashboard, Notifications, Email, Integrations, API |

## Branches

Supports CRUD, branch manager/contact, address, active status, and a single default branch per organization.

## Shifts

Supports CRUD, default shift, working hours, breaks, grace time, and overtime threshold. Shift **assignments** remain an HR operational page.

## HR Configuration

Working days, leave policies, leave approvers, and attendance rules are stored on `organizations.settings` and consumed by HRMS services.

## Assets (Future Module)

The Assets module remains in the database and architecture but is hidden from navigation until the production lifecycle is complete. See `config/organization_settings.php` → `future_modules.assets`.

## Permissions

- `settings.manage` — hub and profile
- `organization.branches.view|manage`
- `organization.shifts.view|manage`
- `organization.hr_config.manage`
- Existing `hrms.view`, `leave.*`, `attendance.*` remain valid fallbacks
