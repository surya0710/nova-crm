# Administrator Guide — Platform Stabilization (11.8.1)

## Organization Settings

Use **Settings → Organization Settings** for all tenant configuration: branches, shifts, working days, leave policies, attendance rules, branding, email, API, and billing.

## Permissions

New permission slugs (seeded via migration):

- `organization.branches.view|manage`
- `organization.shifts.view|manage`
- `organization.hr_config.manage`
- `recruitment.meeting.manage`

Corporate / Startup / Agency / Healthcare / Education templates inherit these through the HR role bundles in `config/rbac.php` after re-seed / migrate.

## Assets module

Hidden from navigation as a **future module**. Database tables and routes remain for upcoming HR integrations.

## Empty states

Users without a linked employee record see a friendly empty state (HTTP 200), not a 403. Managers with no direct reports see “No employees assigned.”
