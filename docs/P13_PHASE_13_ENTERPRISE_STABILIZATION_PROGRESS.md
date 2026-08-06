# Phase 13.0 — Enterprise Stabilization & Production Readiness

**Project:** Konnect Nex  
**Platform:** Core Platform (CRM + HRMS + Project Management)  
**Status:** In Progress  
**Priority:** Critical (Release Candidate track)  
**Started:** 2026-08-06  
**Target gate:** RC1 (pilot customer ready)

---

## Objective

Validate every completed module, eliminate production-critical defects, improve performance and security confidence, and complete operational documentation. **No new business modules** unless required to fix a production-critical defect.

> **Naming note:** Phases 13.1–13.4 are product/design/frontend *blueprints* (documentation only). This document is the **enterprise stabilization / RC1** track and is independent of those blueprints. Commercial UX/production polish also continues under [release/production-readiness.md](release/production-readiness.md) (Phase 14.9).

---

## Architecture constraints (unchanged)

| Constraint | Status |
| --- | --- |
| No architectural redesign | Enforced |
| No refactor-for-refactor | Enforced |
| Business logic stays in services | Enforced |
| Controllers remain thin | Enforced |
| Dynamic RBAC, OrganizationScope, Metadata, Audit, Notification, Dashboard, Queue, API platforms unchanged | Enforced |

---

## Overall progress

| Work package | Area | Status | Confidence |
| --- | --- | --- | --- |
| WP1 | HRMS Verification | In progress | Medium — strong automated coverage; operator SOPs added |
| WP2 | CRM Verification | In progress | Medium — LeadVisibilityService confirmed as listing SoT |
| WP3 | Project Management Verification | In progress | Medium — large test surface; portal covered |
| WP4 | API Verification | In progress | Medium — Sanctum + permission + org middleware; contract spot-check ongoing |
| WP5 | RBAC Verification | In progress | High for pattern; full permission matrix audit ongoing |
| WP6 | Multi-Tenancy Verification | In progress | High for OrganizationScope + middleware; cross-tenant suites exist |
| WP7 | Queue & Background Jobs | In progress | Medium — monitoring + hardening landed (1.2.x); shared-host cron documented |
| WP8 | Import & Export Validation | In progress | Medium — lead/customer import diagnostics strong |
| WP9 | Performance Optimization | Not started | — |
| WP10 | Security Review | In progress | Medium — CSRF exceptions documented; role-name checks limited to platform guard |
| WP11 | Organization Provisioning | Verified (code) | High — `OrganizationProvisioningService` idempotent path |
| WP12 | SOPs & Operational Documentation | In progress | High for ops; business SOPs added this phase |
| WP13 | Logging & Monitoring | In progress | Medium — queue/audit/error SOPs exist |
| WP14 | Automated Testing | In progress | Baseline run underway |
| WP15 | UAT Scenarios | Drafted | Not executed with stakeholders |
| WP16 | Release Readiness | Partial | Checklist exists; RC1 not signed off |

**RC1 readiness:** Not yet. Gate criteria at bottom remain open until baseline regression is green and WP9–10 findings are closed or accepted.

---

## Verification checklist

### WP1 — HRMS

| Capability | Automated tests | Manual / code review | Notes |
| --- | --- | --- | --- |
| Employee Management | `HrmsEmployeeManagementTest`, `EmployeeProvisioningTest` | Pending | |
| Attendance Enterprise | `HrmsAttendanceTest`, `AttendanceOvertime*`, `AttendanceVersioningAndSnapshotTest`, `AttendanceDashboardTest` | Code: `AttendanceLockService` freeze→lock→snapshot | SOP-OPS-002 |
| Leave Management | `HrmsLeaveTest` | Pending | |
| Payroll Enterprise | `HrmsPayroll*` (foundation, calculation, finance, statutory, publication, enterprise enhancement) | Pending | SOP-OPS-003 |
| Indian Income Tax & TDS | `HrmsIncomeTaxTdsTest` | Pending | SOP-OPS-004 |
| Recruitment | `HrmsRecruitment*`, `RecruitmentApiTest`, `CandidatePortalTest` | Pending | SOP-OPS-005 |
| ESS | `HrmsEssTest`, mobile ESS API tests | Pending | |
| HRMS Mobile APIs | `Hrms/MobileApi/*` | Routes via `api_hrms.php` + Sanctum | |
| Documents | `HrmsEmployeeDocumentsTest` | Pending | |
| Notifications | Covered in ESS/mobile + notification platform tests | Pending | |
| Permissions / tenant / reports / dashboard / exports / queues | Mixed feature coverage | Cross-check with WP5–8 | |

### WP2 — CRM

| Capability | Evidence | Status |
| --- | --- | --- |
| Leads / visibility | `LeadVisibilityService` used by controllers, policy, API, search, export, widgets | OK (SoT) |
| Customers / contacts / opportunities | Feature tests + policies | In progress |
| Activities / timeline | Controllers + `TimelineService` unit tests | In progress |
| Saved filters | Metadata saved-filter integration tests | In progress |
| Imports / exports | `LeadImport*`, `CustomerImportTest`, `ExportCenterTest` | In progress |
| Dashboard / reports / search / notifications / workflows | Feature suites | In progress |
| Stabilization bugfixes | `docs/STABILIZATION_BUGFIX_01` … `_05` | Historical — confirm still green |

### WP3 — Project Management

| Capability | Evidence | Status |
| --- | --- | --- |
| Projects / tasks / resources / planning / agile / portfolio | Large Feature + Unit suite (~100 project-related tests) | In progress |
| Client Portal | `ClientPortalTest`, `routes/portal.php`, portal middleware | In progress |
| Dashboards / reports / APIs / workflow events | Dedicated feature tests | In progress |
| End-to-end lifecycle | UAT scenario drafted (WP15) | Not executed |

### WP4 — API

| Check | Evidence | Status |
| --- | --- | --- |
| Auth | Sanctum `auth:sanctum` on `/api/v1` | OK |
| Org context | `set.organization`, `ensure.organization`, `organization.api` | OK |
| Permissions | `permission:*` + `api.access` | OK |
| Domains | CRM, Projects, Recruitment, Payroll (`api_payroll.php`), Tax (`api_income_tax.php`), HRMS (`api_hrms.php`), Portal (`v1/portal/{slug}`), Notifications | Mapped |
| Response format / errors | `ApiResponse` exception rendering in `bootstrap/app.php` | OK |
| Pagination / filtering / validation / backward compatibility | Spot-check + feature API tests | In progress |
| Throttle | `throttle:api`; portal `throttle:60,1` | OK |

### WP5 — RBAC

| Check | Result |
| --- | --- |
| Middleware permission checks | `EnsureUserHasPermission` → `hasAnyPermission` (dynamic) |
| Policies | Broad policy catalog under `app/Policies` (permission-based) |
| Role-name checks in tenant app code | Only platform-guard usages (`PlatformUser::role`, impersonation support role) — **not** tenant RBAC |
| Config sources | `config/rbac.php`, dynamic RBAC / templates via provisioning |

### WP6 — Multi-tenancy

| Check | Result |
| --- | --- |
| `OrganizationScope` via `BelongsToOrganization` | Present on tenant models |
| `SetCurrentOrganization` / `ensure.organization` | Web + API |
| Portal / careers org resolvers | `portal.organization`, `careers.organization` |
| Cross-tenant tests | Task/Resource multi-tenancy, workflow isolation, many HRMS/CRM suites assert org match |
| Imports reject cross-org owners | Lead import diagnostics | 

### WP7 — Queue & jobs

| Job / area | Path | Notes |
| --- | --- | --- |
| Imports | `ProcessImportSessionJob` | Hardened (tries/timeouts/failed) |
| Exports | `ProcessExportSessionJob` | Hardened |
| Bulk | `ProcessBulkOperationJob` | Hardened |
| Employee provisioning | `BulkProvisionEmployeeUsersJob` | Hardened |
| Payslips | `SendPayslipEmailJob` | Hardened |
| Monitoring | `QueueHealthService`, platform monitoring, `QueueMonitoringTest` | SOP-MON-002, SOP-MNT-007 |
| Shared hosting | `deploy/cron/cpanel-plesk.cron.example` | Documented |

### WP8 — Import & Export

| Domain | Tests / docs | Status |
| --- | --- | --- |
| Leads | `LeadImportTest`, diagnostics, templates | Strong |
| Customers | `CustomerImportTest` | Present |
| Employees / payroll / bank / documents | Mixed payroll finance + export adapters | Spot-check ongoing |
| Large file / invalid / duplicate / rollback | Import validation engine + report tests | Present; load test pending |

### WP9 — Performance

| Area | Status | Notes |
| --- | --- | --- |
| N+1 / eager loading | Not started | Prioritize workspace homes + report endpoints |
| Indexes | Not started | Review hot tables (leads, attendance, payroll results) |
| Pagination | Partially verified via APIs | Confirm list endpoints never unbounded |
| Cache | `CachesWorkspaceHome` / `DashboardCache` | Documented in production-readiness |
| Queue throughput / API latency | Not measured this phase yet | |

### WP10 — Security

| Area | Status | Notes |
| --- | --- | --- |
| Auth / sessions / password reset | Existing Auth feature tests | Review production env flags |
| Authorization | Dynamic RBAC + policies | Ongoing matrix |
| CSRF | Enabled; exceptions: `marketing/track`, `webhooks/marketing/*` | Documented in bootstrap |
| XSS / SQLi | Framework defaults + query builders | Spot-check raw queries |
| Uploads / downloads / signed URLs | Mobile upload validator + document policies | Ongoing |
| Rate limiting | API + portal throttles | OK |
| API tokens | Sanctum | Confirm token abilities/scopes in ops guide |

### WP11 — Organization provisioning

Verified code path: `App\Services\Rbac\OrganizationProvisioningService::provision()`

1. Seed/clone permissions  
2. Apply default permission template  
3. Ensure legacy/system roles + permission sync  
4. `OrganizationUpgradeService::upgrade()` (modules, preferences, dashboard defaults)  
5. Project + task defaults when tables exist  
6. Assign owner to org admin/owner role  

Operator procedure: [SOP-ONB-002](sops/onboarding/SOP-ONB-002-organization-provisioning.md).

### WP12 — SOPs map

| Required topic | Document | Status |
| --- | --- | --- |
| New Organization Setup | SOP-ONB-002 (+ ONB-001…008) | Complete |
| Employee Onboarding | [SOP-OPS-001](sops/business-operations/SOP-OPS-001-employee-onboarding.md) | Added (P13.0) |
| Payroll Processing | [SOP-OPS-003](sops/business-operations/SOP-OPS-003-payroll-processing.md) | Added (P13.0) |
| Attendance Lock Workflow | [SOP-OPS-002](sops/business-operations/SOP-OPS-002-attendance-lock-workflow.md) | Added (P13.0) |
| Tax Declaration Workflow | [SOP-OPS-004](sops/business-operations/SOP-OPS-004-tax-declaration-workflow.md) | Added (P13.0) |
| Recruitment Workflow | [SOP-OPS-005](sops/business-operations/SOP-OPS-005-recruitment-workflow.md) | Added (P13.0) |
| CRM Setup | [SOP-OPS-006](sops/business-operations/SOP-OPS-006-crm-setup.md) | Added (P13.0) |
| Queue Monitoring | SOP-MON-002 | Complete |
| Backup & Restore | SOP-MNT-002 / SOP-MNT-003 | Complete |
| Release Process | SOP-REL-001…005 | Complete |
| Deployment Guide | SOP-DEP-* + `docs/deployment/` | Complete |
| Disaster Recovery | SOP-DR-001…005 | Complete |

### WP13 — Logging & monitoring

| Log domain | Where | Status |
| --- | --- | --- |
| Audit | `AuditLogger` + model `Auditable` | Present |
| Application | `storage/logs/laravel.log` | SOP-MON-004 |
| Queue | `queue_job_runs`, failed jobs | SOP-MON-002 |
| Import | Import session diagnostics | Present |
| API | Standard Laravel + ApiResponse | Confirm PII redaction |
| Notifications | Notification platform + drawer | Present |

### WP14 — Automated testing inventory (approx.)

| Domain bucket | ~Test files | Notes |
| --- | --- | --- |
| HRMS | 66 | Strong payroll/attendance/recruitment |
| Projects | 103 | Largest suite |
| CRM | 35 | Plus marketing suites in Other |
| RBAC / Tenant / Platform | 12 | Supplement with isolation asserts inside domain tests |
| Import / Export | 6 | Plus lead/customer import named suites |
| Queue / Workflow | 8 | Hardening + monitoring |
| API-named | 8+ | Mobile, project, resource, recruitment, lead intake |
| Other | 54 | Auth, metadata, marketing, smoke, etc. |
| **Total** | **286** | `php artisan test --parallel` unavailable (ParaTest 7.x missing) |

Baseline command:

```bash
php artisan test --compact
# or domain filters; see storage/logs/p13-test-baseline.txt
php artisan test --group=smoke
```

### WP15 — UAT scenarios (draft)

#### HRMS — Hire → Attendance → Leave → Payroll → Tax → Payslip

1. Create employee + salary assignment + statutory profile.  
2. Record attendance for period; submit one leave request; approve.  
3. Freeze → lock attendance period (snapshot created).  
4. Create/run payroll; resolve exceptions; approve; publish.  
5. Select tax regime; submit declaration; upload proof; verify; TDS calc.  
6. Employee opens ESS payslip / email delivery via queue.

#### CRM — Lead → Opportunity → Customer

1. Create/import lead; verify visibility rules.  
2. Qualify → opportunity; log activities.  
3. Win → convert/link customer; timeline intact.  
4. Dashboard widgets and search reflect ownership.

#### Projects — Project → Task → Resource → Delivery → Client Portal

1. Create project from template; add members.  
2. Plan tasks + resource allocation.  
3. Progress updates; deliverable submit.  
4. Client portal login; review/approve deliverable; discussion thread.

**UAT status:** Scenarios documented — **not yet signed by customer stakeholders**.

### WP16 — Release readiness

Canonical operator checklists:

- [release/production-readiness.md](release/production-readiness.md)  
- [release/checklist.md](release/checklist.md)  
- [release/smoke.md](release/smoke.md)  
- [UPGRADE.md](../UPGRADE.md)

| Gate | Status |
| --- | --- |
| Configuration review | Pending env-by-env |
| Queue health / scheduled jobs | Docs ready; runtime verify pending |
| Storage links / mail / cache / SSL | Pending |
| Backup strategy / rollback | SOPs ready |
| Automated smoke | `tests/Feature/Smoke/WorkspaceHomesSmokeTest.php` |

---

## Test coverage

| Suite | Result | Date | Log |
| --- | --- | --- | --- |
| `DynamicRbacTest` | **12 passed** (22 assertions, ~539s) | 2026-08-06 | `storage/logs/p13-DynamicRbacTest.php.txt` |
| Critical suite batch (LeadVisibility, IncomeTax, ClientPortal, Queue, Import diagnostics, Organization) | Running | 2026-08-06 | `storage/logs/p13-test-baseline.txt` |
| Full suite | Not completed (no ParaTest; MySQL RefreshDatabase ~20s/test) | — | — |
| Smoke group | Pending | — | — |

**Runner notes:** Use sequential `php artisan test path/to/Test.php` against a clean `novacrm_testing` DB. Interrupted parallel/overlapping runs corrupt MySQL migrate state (see P13-ENV-002). Windows `Start-Process` stdout redirect may leave PHP alive after PHPUnit prints the summary — poll for `Tests:` then stop.

Update this table after each baseline run. Document failures under **Known issues**.

---

## Known issues

| ID | Severity | Area | Description | Status |
| --- | --- | --- | --- | --- |
| P13-ENV-001 | Low | Tooling | `php artisan test --parallel` requires ParaTest 7.x; not installed | Accepted for RC tooling; run sequential suites |
| P13-ENV-002 | Medium | Test infra | Concurrent/interrupted MySQL `RefreshDatabase` leaves `novacrm_testing` half-migrated (`migrations` missing, FK errno 150, table-already-exists). Fix: drop/recreate **testing DB only**, then re-run one suite | Mitigated operationally (XAMPP mysql drop/create); consider SQLite in-memory for CI speed |
| P13-ENV-003 | Low | Performance | Feature tests ~15–20s each on MySQL migrate-per-test; full suite impractical locally without DB strategy change | Accepted for local; CI should use faster DB or migrate-once |
| P13-CRM-001 | High | CRM / RBAC | Sales Executive could update leads but lacked `bulk.crm` / `bulk.view`, so bulk status change aborted with 403 before visibility filtering (`LeadVisibilityTest::test_bulk_filtered_actions_only_affect_visible_leads`) | **Fixed** — added `bulk.view`, `bulk.crm` to `sales-executive` in `config/rbac.php` (still scoped by LeadVisibilityService). Re-provision/upgrade orgs to sync system roles |
| P13-DOC-001 | Info | Phase naming | Phase 13.1–13.4 design blueprints vs 13.0 stabilization | Clarified in this doc |

Historical stabilization fixes (confirm still green):

- [STABILIZATION_BUGFIX_01_LEAD_METADATA_FILTERS.md](STABILIZATION_BUGFIX_01_LEAD_METADATA_FILTERS.md)  
- [STABILIZATION_BUGFIX_02_CUSTOMER_METADATA_FILTERS.md](STABILIZATION_BUGFIX_02_CUSTOMER_METADATA_FILTERS.md)  
- [STABILIZATION_BUGFIX_03_SAVED_FILTERS.md](STABILIZATION_BUGFIX_03_SAVED_FILTERS.md)  
- [STABILIZATION_BUGFIX_04_INVOICE_EMAIL.md](STABILIZATION_BUGFIX_04_INVOICE_EMAIL.md)  
- [STABILIZATION_BUGFIX_05_REVENUE_MAIL_SUBSYSTEM.md](STABILIZATION_BUGFIX_05_REVENUE_MAIL_SUBSYSTEM.md)

---

## Performance results

| Scenario | Metric | Target | Actual | Status |
| --- | --- | --- | --- | --- |
| Workspace home load | TTFB / total | ≤ 2.5s typical org | TBD | Not measured |
| API list (paginated) | p95 latency | TBD | TBD | Not measured |
| Import 10k rows | Duration / memory | TBD | TBD | Not measured |
| Payroll calculate (org size) | Duration | TBD | TBD | Not measured |

---

## Security review

| Finding | Severity | Disposition |
| --- | --- | --- |
| CSRF exceptions limited to marketing beacon + signed webhooks | Info | Accept |
| Platform role string checks isolated to platform users | Info | Accept (not tenant RBAC) |
| Full upload/download/signed-URL matrix | — | Pending |
| Cross-tenant API IDOR spot-check | — | Pending |
| Production `APP_DEBUG=false` / secure cookies | — | Env checklist |

---

## UAT status

| Scenario | Owner | Status |
| --- | --- | --- |
| HRMS hire→payslip | TBD | Draft only |
| CRM lead→customer | TBD | Draft only |
| Projects→portal | TBD | Draft only |

---

## Release readiness / production checklist (RC1)

- [ ] All critical modules pass regression (WP1–3)  
- [ ] No open critical security issues (WP10)  
- [ ] APIs stable; contracts documented (WP4)  
- [ ] Queue processing reliable on target hosting (WP7)  
- [ ] Multi-tenancy verified incl. cross-tenant negative tests (WP6)  
- [ ] RBAC validated; no tenant role-name checks (WP5)  
- [ ] Imports/exports production-ready (WP8)  
- [ ] SOPs complete (WP12) — **business ops SOPs added**  
- [ ] UAT scenarios pass with stakeholders (WP15)  
- [ ] Operator release checklist executed on staging (WP16)  
- [ ] Pilot customer deployment plan approved  

**RC1 decision:** Pending  

---

## Out of scope (reminder)

New CRM/HRMS/PM features, AI enhancements, Finance ERP, Procurement, Asset Management, Helpdesk, biometric integrations, native mobile apps — **excluded**. Production-critical bug fixes only.

---

## Related documents

| Doc | Role |
| --- | --- |
| [P13_PHASE_13_1_PROGRESS.md](P13_PHASE_13_1_PROGRESS.md) … [13_4](P13_PHASE_13_4_PROGRESS.md) | Design/IA blueprints (separate track) |
| [release/production-readiness.md](release/production-readiness.md) | Commercial production gate |
| [release/checklist.md](release/checklist.md) | Operator deploy checklist |
| [sops/INDEX.md](sops/INDEX.md) | SOP catalog |
| [deployment/queues-and-scheduler.md](deployment/queues-and-scheduler.md) | Queue runbook |
| [HRMS_PLATFORM_RUNTIME_CONTRACT.md](HRMS_PLATFORM_RUNTIME_CONTRACT.md) | HRMS runtime contract |

---

## Changelog

| Date | Change |
| --- | --- |
| 2026-08-06 | Phase 13.0 tracker opened; inventory of tests/APIs/RBAC/tenancy/queues; business-ops SOPs added; baseline regression started |
