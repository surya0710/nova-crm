# HRMS & Recruitment Workspace — Enterprise UX Reference

Phase **14.5** migrated HRMS & Recruitment onto the shared platform established in Phases 14.1–14.4. Use with [crm-reference-implementation.md](./crm-reference-implementation.md) and [projects-workspace.md](./projects-workspace.md).

---

## Workspace home

| Piece | Path |
|-------|------|
| Route | `hrms.home` → `/hrms/home` |
| Controller | `App\Http\Controllers\Hrms\HrmsHomeController` |
| Aggregator | `App\Services\Hrms\HrmsWorkspaceHomeService` |
| View | `resources/views/hrms/home.blade.php` |
| Layout | `x-layouts.workspace-home` |

**Widgets / regions:** Employee summary · Attendance today · Attendance percentage · Employees on leave · Pending leave · Birthdays · Work anniversaries · New joiners · Recruitment pipeline · Open positions · Interview schedule · Payroll summary · Performance snapshot · Department overview · Assets assigned · Upcoming holidays · Recent activities · Attention rail · Quick actions · My HR shortcuts.

**Personalization:** reads `UserUiPreference.dashboard_layout['hr']` (layout editor reserved; same pattern as CRM/Projects).

**Legacy:** `hrms.dashboard` remains for HR dashboard permission holders (More → Legacy HR Dashboard) and uses the same enterprise chrome.

---

## Navigation

Configured in `config/navigation.php`:

- Workspace landing: `hrms.home`
- Primary: Employees (directory/org structure), Attendance, Leave, Shifts, Payroll, Performance, Recruitment, Assets
- Extended: Documents, Reports, Configuration (Departments, Branches, Designations, Holiday Calendar, Leave Types, Shift Management), Announcements, Exit, Calendar, Manager Dashboard
- My HR / ESS: dashboard, profile, documents, attendance, leave, payroll
- Route map: `hrms.*`, `ess.*` → `hr`

Breadcrumbs on pages: **HR Home → Section → Record**.

Favorites / recents / pins continue via shell `NavigationContextManager`.

---

## Entity & module patterns

| Area | Layout | Notes |
|------|--------|-------|
| Listings | `x-layouts.entity-listing` + `x-tables.table` | Employees, attendance, leave, candidates, payroll, assets, … |
| Detail | `x-layouts.entity-detail` + `x-entity.section` | Employee show, candidate, leave application, payroll run, … |
| Forms | `x-layouts.create` / `edit` + `x-forms.footer` | Employee create/edit; shared form partials |
| Dashboards | `workspace-home` / `dashboard` / `analytics` | HR home, leave, recruitment, payroll, performance |
| Collaboration | `x-activity.timeline` | Employee employment history timeline |

**Reference module:** Employees (`resources/views/hrms/employees/*`) — listing, create/edit, and profile sections (personal, employment, organization, emergency contacts, documents, assets, attendance/leave history, related payroll/performance/timeline). Same role Leads played for CRM.

---

## Search & command palette

Registered in `AppServiceProvider`:

**Search scopes:** `employees` · `candidates` · `job_openings` · `leave_requests` · `attendance` · `assets` · `hr_documents` · `performance_reviews` (plus CRM/Projects/legacy).

**Palette group:** HR — Open Home, Create Employee, Create Job Opening, Apply Leave, Mark Attendance, Open Recruitment, Search Employees/Candidates, Leave Dashboard, Payroll, Reports, My HR.

---

## Empty states

`x-ui.empty-state-preset` variants added for HR:

`employees` · `attendance` · `leave` · `recruitment` · `candidates` · `assets` · `payroll` · `performance` · `documents`

---

## Migration checklist (for nested polish)

1. Keep controllers/services/policies unchanged  
2. Use `x-app-layout` **without** legacy `header` slot  
3. Prefer `x-ui.*` / `x-forms.*` / `x-tables.*` over indigo/slate one-offs when touching a file  
4. Breadcrumbs from `hrms.home`  
5. Empty presets for zero-data states  
6. No invented entities or calculation changes  

---

## Out of scope (later waves)

Marketing, Analytics homes, Platform/Organization Administration, Configuration Hub, Support, public Careers website redesign, mobile application.
