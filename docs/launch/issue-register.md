# Deliverable 14 — Pilot Issue Register

Central register for Program 15.8 findings. No **Blocker** may remain open before General Availability.

## Severity

| Level | Meaning |
|-------|---------|
| Blocker | Prevents go-live / data loss / security breach |
| High | Major workflow broken; workaround painful |
| Medium | Partial impact; documented workaround |
| Low | Cosmetic / docs / polish |

## Register

| Issue ID | Severity | Module | Description | Reproduction | Resolution | Owner | Status |
|----------|----------|--------|-------------|--------------|------------|-------|--------|
| ISSUE-P15.8-001 | Medium | Onboarding / Import | SOP-ONB-006 implied HRMS & Projects spreadsheet import; platform only ships Lead + Customer Import Platform adapters | Follow SOP-ONB-006 for employees/projects CSV | SOP-ONB-006 v1.1 clarified; HRMS/Projects adapters remain product backlog | Implementation / Product | Resolved (docs); backlog open |
| ISSUE-P15.8-002 | Medium | Launch / Ops | Production infrastructure validation (TLS, Supervisor, cron, Redis, mail, backups) not executable from XAMPP evidence | Attempt DEP SOPs on local Apache/XAMPP | Complete staging/production deploy report; keep local command matrix as app-level evidence only | DevOps | Open |
| ISSUE-P15.8-003 | Low | Docs | Pre-15.8 `docs/launch/` files were selection stubs without evidence pack | N/A | Expanded 15.8 evidence pack | Program Lead | Resolved |
| ISSUE-P15.8-004 | Low | CRM | No dedicated Contact model/importer; “contacts” map to lead/customer name fields | Import “contacts” per CAT script | Clarify in customer docs / SOP-ONB-006 | Product Docs | Open |
| ISSUE-P15.8-005 | Low | Ops | `storage:link` reports link already exists on re-run | Re-run `php artisan storage:link` after first success | Expected idempotent behavior; document as Pass | DevOps | Resolved |

## Triage rules

- Label tickets `pilot` in the support/engineering tracker ([issue-tracking.md](./issue-tracking.md)).
- Blockers escalate to Program Lead same day.
- Enhancements route to [enhancement-backlog.md](./enhancement-backlog.md).

## GA gate

| Criterion | Status |
|-----------|--------|
| Zero open Blockers | Met (none filed) |
| High issues have owners + dates | N/A |
| Medium docs gaps accepted or scheduled | ISSUE-001 / 002 / 004 tracked |
