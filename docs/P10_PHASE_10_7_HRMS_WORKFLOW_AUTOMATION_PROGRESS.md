# Phase 10.7 — HRMS Workflow Automation Progress

## Status

**WP1 complete, WP2 complete, WP3 complete** — required workflow test suite **PASS** (30/30).

Broader `HrmsFoundationTest`: 9/11 (2 pre-existing RBAC/navigation failures unrelated to workflow registration).

## Objective

Connect existing HRMS domain events and trigger definitions to the existing Workflow Automation Platform. Extend the platform only — do not create a second HRMS workflow engine.

---

## WP1 — Audit Existing Workflow Infrastructure

### Runtime path (unchanged)

```text
HRMS domain service write
        │
        ▼
WorkflowDomainEvent (ShouldDispatchAfterCommit)
        │
        ▼
RunTriggeredWorkflows (queue: workflows, ShouldQueueAfterCommit)
        │
        ├── TenantContext restore
        ├── active workflows by exact trigger_type
        ├── ConditionEvaluator (snapshot)
        └── ActionDispatcher → domain services
```

### Components inspected

| Area | Location | Finding |
| --- | --- | --- |
| Trigger/action catalog | `config/workflows.php` | CRM + Project triggers registered; HRMS triggers were absent before WP2 |
| HRMS trigger placeholders | `config/hrms.php` → `workflow_triggers` | Documented keys matching event `trigger()` strings |
| Event base | `App\Events\WorkflowDomainEvent` | Immutable snapshot + payload; after-commit |
| Listener | `App\Listeners\RunTriggeredWorkflows` | Queued, tenant-scoped, idempotent executions |
| Conditions | `App\Workflow\ConditionEvaluator` | Entity-agnostic; no HRMS-specific operators needed |
| Actions | `App\Workflow\Actions\*` | Existing handlers; `notify_user` is entity-agnostic |
| Action dispatch | `App\Workflow\ActionDispatcher` | Entity check used `strtolower(class_basename)` — broke multi-word models |
| Definition service | `App\Services\WorkflowService` | Validates triggers only against `config/workflows.triggers` |
| Event wiring | `AppServiceProvider::boot` | HRMS events already registered with `RunTriggeredWorkflows`; **bug:** `EmployeeUpdated` was listed without a `use` import (registered wrong FQCN) — fixed in WP2 |
| Queue | `RunTriggeredWorkflows::$queue = 'workflows'` | Unchanged |
| Audit | Execution logs + domain `Auditable` | Unchanged |
| CRM / Project triggers | `lead.*`, `customer.*`, `project.*`, `task.*`, … | Must remain behaviorally identical |

### Extension points required for HRMS

1. **Register HRMS triggers** in `config/workflows.php` (blocking for workflow builder / `WorkflowService` validation).
2. **Keep a single catalog** shared with `config/hrms.php` to prevent drift.
3. **Expand `notify_user` entities** so HRMS triggers can attach a usable action.
4. **Align ActionDispatcher entity resolution** with snake_case catalog entities (`leave_application`, not `leaveapplication`).
5. **Later WPs (not WP2):** HRMS-specific actions (if needed), scheduled `employee.probation_ending` / document expiry emitters if missing, optional dedicated `employee.status_changed` event.

### Naming notes (WP2 priority list ↔ canonical keys)

| Spec / informal name | Canonical key (event + catalog) |
| --- | --- |
| `leave.applied` | `leave.submitted` |
| `employee.document_uploaded` | `employee_document.uploaded` |
| `employee.status_changed` | *(no event)* — status changes emit `employee.updated` + audit `employee_status_changed` |
| `recruitment.application.created` | `recruitment.application_submitted` |
| `recruitment.interview.scheduled` | `recruitment.interview_scheduled` |
| `recruitment.offer.created` | `recruitment.offer_generated` |
| `recruitment.offer.accepted` | `recruitment.offer_accepted` |

No aliases were introduced. Canonical event keys are the source of truth.

---

## WP2 — Register HRMS Workflow Triggers

### Changes

| File | Change |
| --- | --- |
| `config/hrms_workflow_triggers.php` | **New** shared catalog (112 triggers), including `employee.updated` / `department_changed` / `manager_changed` |
| `config/hrms.php` | Requires shared catalog; comments updated for Phase 10.7 |
| `config/workflows.php` | `array_merge` CRM/Project triggers with HRMS catalog; `notify_user` entities include HRMS subjects |
| `app/Providers/AppServiceProvider.php` | Added missing imports: `EmployeeUpdated`, `MarketingLeadImported`, `RequisitionApproved` so listener registration resolves correct FQCNs |
| `app/Workflow/ActionDispatcher.php` | Resolve entity from trigger catalog / `Str::snake(class_basename)` |
| `tests/Feature/HrmsFoundationTest.php` | Assert HRMS triggers **are** registered |
| `tests/Feature/HrmsWorkflowTriggerRegistrationTest.php` | **New** WP2 registration + listener + create-workflow coverage |
| `tests/Feature/WorkflowFoundationTest.php` | Scope audit-log count by `auditable_type` + `auditable_id` (was colliding on numeric id) |
| `docs/HRMS_PLATFORM_RUNTIME_CONTRACT.md` | Workflow section updated |

### Guarantees

- No second workflow engine
- No changes to CRM/Project trigger behavior (additive merge only)
- No business logic in controllers or HRMS services for workflow execution
- Forward-only; no migrations in WP2
- Existing APIs/mobile APIs untouched

### Known remaining gaps (future WPs)

- `employee.probation_ending` is registered but has no emitter yet
- Dedicated `employee.status_changed` event not created (status → `employee.updated`)
- Non-notification actions (`create_task`, etc.) still limited to CRM/Project entities unless expanded later

---

## WP3 — HRMS Workflow Runtime Integration

### Changes

| File | Change |
| --- | --- |
| `tests/Feature/Hrms/HrmsWorkflowRuntimeTest.php` | **New** runtime integration suite for HRMS triggers through `RunTriggeredWorkflows` |

### Coverage

- Employee: `created` / `updated` / `salary_assigned` / `employee_document.uploaded` / `department_changed` / `manager_changed`; asserts no `employee.document_uploaded` alias
- Leave: submitted / approved / rejected / cancelled + `notify_user` + tenant isolation
- Attendance corrections: submitted / approved / rejected; queued listener (`workflows` + after-commit); attendance fields unchanged
- Payroll: period locked / run completed / published / paid; payroll state unchanged; mutating CRM actions rejected for payroll entities
- Tax/TDS: declaration + proof + `tds.calculated`; tax rows unchanged; no tax-mutating workflow actions
- Recruitment: canonical keys only (no dotted aliases)
- Notification runtime: recipient / org / entity / payload / no cross-tenant / no duplicates on redelivery
- Conditions: department, branch, employee status, leave, attendance, payroll, tax declaration, document type/expiry via existing `ConditionEvaluator`
- After-commit + queue safety: rolled-back transactions do not dispatch
- Idempotency / retry / stale lease recovery reusing existing runtime

### Guarantees

- No second workflow engine
- No LeaveService / payroll / tax / attendance calculation changes
- Condition support not extended (existing evaluator sufficient)
- Existing CRM `WorkflowRuntimeTest` / `WorkflowFoundationTest` remain green

---

## Test Report

**Date:** 2026-08-10  
**Environment:** PHPUnit 11.5.55 / PHP 8.2.12 / MySQL `novacrm_testing`  
**Command:**

```bash
php vendor/phpunit/phpunit/phpunit --filter "HrmsWorkflowRuntimeTest|WorkflowRuntimeTest|WorkflowFoundationTest|HrmsWorkflowTriggerRegistrationTest" --testdox
```

### Phase 10.7 required suite — PASS

| Suite | Result | Tests | Notes |
| --- | --- | --- | --- |
| `HrmsWorkflowRuntimeTest` | **PASS** | 11/11 | WP3 runtime integration |
| `HrmsWorkflowTriggerRegistrationTest` | **PASS** | 4/4 | WP2 registration, listeners, `notify_user` entities, WorkflowService create |
| `WorkflowFoundationTest` | **PASS** | 6/6 | CRM listener wiring + definition lifecycle unchanged |
| `WorkflowRuntimeTest` | **PASS** | 9/9 | Runtime/idempotency/concurrency regression green |

**Totals:** **30 tests, 399 assertions — OK**  
**Duration:** ~22m 47s  

### Per-test checklist (required suite)

#### Hrms Workflow Runtime
- [x] Employee workflows execute through platform runtime
- [x] Leave workflows notify user with tenant isolation
- [x] Attendance correction workflows queue without mutating attendance
- [x] Payroll workflows notify without changing payroll state or actions
- [x] Tax and tds workflows execute without tax mutation actions
- [x] Recruitment workflows use canonical triggers without aliases
- [x] Notification runtime is scoped to recipient org entity and is not duplicated
- [x] Condition evaluator supports hrms snapshot fields
- [x] After commit and queue safety for hrms events
- [x] Hrms event execution is idempotent and retries without repeating completed actions
- [x] Stale lease redelivery recovers hrms execution

#### Hrms Workflow Trigger Registration
- [x] Hrms triggers are registered in workflow catalog
- [x] Priority hrms events are wired to workflow listener
- [x] Notify user supports hrms entities
- [x] Workflow service accepts hrms trigger with notify user

#### Workflow Foundation
- [x] Domain listener is registered and queues after commit
- [x] Service persists nested definition and controls lifecycle
- [x] Tenant scope is applied to all workflow models
- [x] Partial update carries complete active definition into an immutable version
- [x] Definition updates preserve historical log references
- [x] Service and notification runtime reject external action urls

#### Workflow Runtime
- [x] Queue visibility timeout exceeds listener timeout and retry budget
- [x] Event execution is tenant safe idempotent and logs action outcome
- [x] Event snapshot refreshes complete persisted state
- [x] Concurrency limit defers execution without losing it
- [x] Deferred execution acquires capacity on redelivery and completes
- [x] Redelivery recovers stale lease for same event
- [x] Failed execution can retry without repeating completed actions
- [x] Failed action rolls back its database side effect but keeps diagnostic logs
- [x] Metadata action validates and updates projection through form service

### Full `HrmsFoundationTest` (broader regression) — 9/11

```bash
php vendor/phpunit/phpunit/phpunit --filter HrmsFoundationTest --testdox
```

| Result | Count |
| --- | --- |
| Passed | 9 |
| Failed | 2 |
| Assertions | 93 |

**Pre-existing failures (not caused by Phase 10.7 WP1/WP2/WP3):**

| Test | Failure | Classification |
| --- | --- | --- |
| `test_hrms_and_ess_routes_are_permission_protected` | HR user gets **403** on `hrms.dashboard` (expected 200) | RBAC / role-grant / route auth — outside workflow registration |
| `test_sidebar_shows_hrms_and_ess_links_based_on_permissions` | Response missing **"HR Dashboard"** | Navigation / permission visibility — outside workflow registration |

**Log:** `storage/logs/p10_7_hrms_foundation_test_run.txt`

### Fixes applied during verification

1. Missing `use` imports in `AppServiceProvider` for events already listed in `Event::listen([...], RunTriggeredWorkflows::class)`:
   - `EmployeeUpdated`
   - `MarketingLeadImported`
   - `RequisitionApproved`
2. `WorkflowFoundationTest` audit count now filters by `auditable_type` + `auditable_id` (numeric-id collision with seeded audit rows).

### Verdict

Phase 10.7 **WP1 + WP2 + WP3 required workflow tests are green**.  
Two `HrmsFoundationTest` UI/RBAC failures remain and are **out of scope** for workflow automation; track separately if needed.
