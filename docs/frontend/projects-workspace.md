# Projects Workspace — Enterprise UX Reference

Phase **14.4** migrated the Projects & Enterprise Portfolio Management (EPM) workspace onto the shared platform established in Phases 14.1–14.3. Use this document together with [crm-reference-implementation.md](./crm-reference-implementation.md).

---

## Workspace home

| Piece | Path |
|-------|------|
| Route | `projects.home` → `/projects/home` |
| Controller | `App\Http\Controllers\Projects\ProjectsHomeController` |
| Aggregator | `App\Services\Projects\ProjectsWorkspaceHomeService` |
| View | `resources/views/projects/home.blade.php` |
| Layout | `x-layouts.workspace-home` |

**Widgets / regions:** My projects, My tasks, Overdue tasks, Upcoming milestones, Portfolio/Program summaries, Budget overview, Risk overview, Favorite/Recent projects, Recent activity, Attention rail, Resource shortcuts, Quick actions.

**Personalization:** reads `UserUiPreference.dashboard_layout['projects']` (layout editor reserved; same pattern as CRM).

---

## Navigation

Configured in `config/navigation.php`:

- Workspace landing: `projects.home`
- Primary: Projects, Tasks, Milestones, Resources (planner/capacity/workload/allocations/calendars), Portfolios, Programs
- Extended: Risks, Issues, Budgets, Forecasts, Reports
- Route map: `projects.*`, `portfolios.*`, `programs.*`, `resources.*`, `risks.*`, `issues.*`, `tasks.*`, `portfolio-reports.*` → `projects`

Breadcrumbs on pages: **Projects Home → Section → Record**.

Favorites / recents / pins continue via shell `NavigationContextManager` (unchanged storage).

---

## Entity & module patterns

| Area | Layout | Notes |
|------|--------|-------|
| Listings | `x-layouts.entity-listing` + `x-tables.table` | Projects, Tasks, Portfolios, Programs, Risks, Issues, hubs |
| Detail | `x-layouts.entity-detail` + `x-entity.section` | Project show (summary, members, milestones, budget, links) |
| Forms | `x-layouts.create` / `edit` + `x-forms.footer` | Project create/edit; other modules use same shells |
| Task board | `x-layouts.entity-listing` | Kanban columns preserved |
| Dashboards / analytics | `entity-detail` / `dashboard` / `analytics` | Executive, health, gantt, progress, portfolio executive |
| Collaboration | Shared layouts + activity/timeline where applicable | Collaboration, watching, mentions |

**Hubs (presentation only):**

| Hub | Route |
|-----|-------|
| Milestones | `projects.milestones.hub` |
| Budgets | `projects.budgets.hub` |
| Reports | `projects.reports.hub` |

---

## Search & command palette

Registered in `AppServiceProvider`:

**Search scopes:** `projects`, `tasks`, `portfolios`, `programs`, `risks`, `issues`, `milestones` (plus legacy `all`).

**Palette group:** Projects — Open Home, Create Project/Task, Open Portfolios/Programs/Resource Planner, Search Projects/Tasks, Open Task Board, Open Reports.

---

## Empty states

`x-ui.empty-state-preset` variants added for Projects:

`projects` · `tasks` · `portfolios` · `programs` · `risks` · `issues` · `reports` · `resources` · `milestones`

---

## Migration checklist (for remaining nested polish)

1. Keep controllers/services/policies unchanged  
2. Use `x-app-layout` **without** legacy `header` slot  
3. Prefer `x-ui.*` / `x-forms.*` / `x-tables.*` over indigo/slate one-offs when touching a file  
4. Breadcrumbs from `projects.home`  
5. Empty presets for zero-data states  
6. No invented entities or calculation changes  

---

## Out of scope (later waves)

Marketing, Analytics homes, Platform/Organization Administration, Configuration Hub, Support. HRMS / Recruitment completed in Phase 14.5 — see [hrms-workspace.md](./hrms-workspace.md).
