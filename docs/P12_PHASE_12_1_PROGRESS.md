# P12 Phase 12.1 — Project Foundation Progress

## Phase
Phase 12.1 — Project Foundation & Governance

## Outcome
Organization-scoped Project Management foundation is implemented end-to-end: schema, catalogs, services, RBAC, metadata, dashboard widgets/quick actions, workflow events, notifications, audit, global search, REST APIs, Blade UI, documentation, and tests.

## Delivered

| Area | Status |
| --- | --- |
| Foundation tables (`project_categories`, `project_types`, `project_statuses`, `project_lifecycle_stages`, `projects`, `project_members`, `project_milestones`) | Done |
| Models, factories, services | Done |
| Domain events + workflow triggers | Done |
| Dynamic RBAC permissions + templates (Corporate, Startup, Agency, Healthcare, Education) | Done |
| Metadata entity `project` + custom_fields alias | Done |
| Dashboard widgets + quick actions | Done |
| Global search + audit URL map | Done |
| Web + API controllers/routes | Done |
| Blade UI (dashboard, listing, details, catalogs, members, milestones, timeline) | Done |
| Seeders + org provisioning defaults | Done |
| Documentation (`docs/projects/*`) | Done |
| Feature/unit tests (48 passing) | Done |

## Run

```bash
php artisan migrate
php artisan db:seed --class=ProjectFoundationSeeder
php artisan test tests/Unit/ProjectServiceTest.php tests/Feature/Project*.php
```

## Notes
- Project-level roles (`owner`, `manager`, `delivery_lead`, …) are independent of organization RBAC.
- Archived projects are read-only; completed projects cannot be deleted.
- Project numbers use `PRJ-0001` style; slugs are unique per organization.
- Metadata Platform stores values via the `custom_fields` alias on the `metadata` JSON column.
