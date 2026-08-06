# Projects Workspace — Phase 14.4 Progress

**Status:** Complete  
**Date:** 2026-07-24  
**Scope:** Migrate Projects & Enterprise Portfolio Management (EPM) onto Phase 14.1–14.3 shared layouts/components.

See also: [docs/frontend/projects-workspace.md](./frontend/projects-workspace.md)

---

## Outcome

The Projects workspace is fully migrated to the Enterprise UX Platform: workspace home, navigation, entity templates, search, command palette, and empty states. Business logic, services (aside from thin UI aggregations), APIs, policies, workflows, metadata, and schema are unchanged.

---

## Deliverables

| # | Deliverable | Status |
|---|-------------|--------|
| 1 | Projects Workspace Home | Done — `projects.home` + `ProjectsWorkspaceHomeService` |
| 2 | Navigation | Done — `projects.home` landing + hubs |
| 3 | Project / Task / Portfolio / Program entities | Done |
| 4 | Resources / Risks / Issues | Done |
| 5 | Search & Command Palette | Done |
| 6 | Empty states | Done |
| 7 | Documentation | Done |

---

## Key paths

- Route: `projects.home` → `/projects/home`
- Controller: `app/Http/Controllers/Projects/ProjectsHomeController.php`
- Service: `app/Services/Projects/ProjectsWorkspaceHomeService.php`
- View: `resources/views/projects/home.blade.php`

---

## Next wave

Phase 14.5 — HRMS & Recruitment Workspace Migration.
