# Phase 14.5 Progress — HRMS & Recruitment Workspace Migration

**Status:** Complete  
**Date:** 2026-07-24  
**Scope:** Migrate the complete HRMS & Recruitment workspace onto the shared Enterprise UX Platform (presentation-layer only).

---

## Outcome

HRMS is the **third fully modernized Enterprise Workspace** (after CRM and Projects). All major HR modules adopt AppShell, workspace home, shared navigation, entity templates, dashboards, tables, forms, timelines, search, and command palette — with **no** changes to attendance engines, leave workflows, payroll calculations, recruitment workflows, approval engines, performance calculations, RBAC, APIs, or database schema.

---

## Deliverables

| # | Deliverable | Status | Notes |
|---|-------------|--------|-------|
| 1 | HRMS Workspace Home | Done | `hrms.home` + `HrmsWorkspaceHomeService` + full widget set |
| 2 | HRMS Navigation | Done | Permission-aware menu + Configuration shortcuts |
| 3 | Employee Management | Done | Listing / detail (profile sections) / create / edit / timeline / documents |
| 4 | Attendance Workspace | Done | Index, summary, corrections, show |
| 5 | Leave Management | Done | Dashboard, applications, balances, approval queue, types, holidays |
| 6 | Recruitment Workspace | Done | Dashboard, openings, candidates, applications, interviews, offers, reports |
| 7 | Performance Management | Done | Goals, reviews, appraisals, feedback, dashboards |
| 8 | Assets & Documents | Done | Assets listing/detail + employee documents |
| 9 | Payroll Workspace | Done | Dashboard, runs, payslips, structures, statutory, reports |
| 10 | HR Reports | Done | Recruitment + payroll report shells on shared layouts |
| 11 | HR Collaboration | Done | Shared activity/timeline on employee timeline + entity pages |
| 12 | Search Integration | Done | Employees, candidates, openings, leave, attendance, assets, documents, reviews |
| 13 | Command Palette | Done | `HrmsCommandProvider` (create/search/open/reports shortcuts) |
| 14 | Empty States | Done | Presets for employees, attendance, leave, recruitment, candidates, assets, payroll, performance, documents |
| 15 | Responsive | Done | Shared layouts/tables (responsive breakpoints inherited) |
| 16 | Accessibility | Done | Landmarks via layouts, labeled forms, sr-only search labels pattern |
| 17 | Legacy Cleanup | Done | No remaining `x-slot name="header"` under `hrms/` / `ess/` |
| 18 | Documentation | Done | This file + `hrms-workspace.md` + progress/catalog updates |

---

## Workspace home widgets

Employee summary · Attendance today · Attendance percentage · Employees on leave · Pending leave · Open positions / recruitment pipeline · Interview schedule · Payroll summary · Upcoming birthdays · Work anniversaries · Upcoming holidays · Assets assigned · Performance snapshot · Department overview · Quick actions · Recent activities · Attention rail · My HR shortcuts.

Personalization reads `UserUiPreference.dashboard_layout['hr']` (same pattern as CRM/Projects).

---

## Key paths

### Route
- `hrms.home` → `/hrms/home` (`HrmsHomeController`)

### Aggregator
- `app/Services/Hrms/HrmsWorkspaceHomeService.php`  
  Reuses `HrmsDashboardService` for HR metrics; adds recruitment/payroll/performance/department widgets.

### View
- `resources/views/hrms/home.blade.php` — `x-layouts.workspace-home`

### Navigation
- `config/navigation.php` — workspace `hr.route = hrms.home`; expanded menus + Configuration shortcuts

### Search providers (`AppServiceProvider`)
- `HrmsEmployeeSearchProvider`
- `HrmsCandidateSearchProvider`
- `HrmsJobOpeningSearchProvider`
- `HrmsLeaveSearchProvider`
- `HrmsAttendanceSearchProvider`
- `HrmsAssetSearchProvider`
- `HrmsDocumentSearchProvider`
- `HrmsPerformanceReviewSearchProvider`

### Command palette
- `HrmsCommandProvider` — Open HR Home, Create Employee/Job Opening, Apply Leave, Mark Attendance, Open Recruitment, Search Employees/Candidates, Leave, Payroll, Reports, My HR

---

## Verification

```bash
php artisan route:list --name=hrms.home
php artisan view:cache
```

Smoke: HR Home widgets, Employees profile sections, Attendance/Leave hubs, Recruitment candidates, Payroll index, global search scopes (`employees`, `candidates`, …), command palette group **HR**.

---

## Out of scope (later waves)

Organization Administration, Configuration Hub, Marketing, Analytics homes, Support (tenant), Customer Portal, public Careers website redesign, mobile application.

---

## Next waves

Marketing / Analytics / Administration workspace migrations using the same CRM → Projects → HRMS reference patterns. Platform Administration is complete in Phase 14.6.
