# P9 Phase 9.2 Impact / QA Report — Workflow Platform

## Phase

Phase 9.2 — Workflow Platform Foundation

## Outcome

Konnect Nex now has a tenant-safe, event-driven Workflow Platform for CRM automation. Administrators can define nested conditions and sequential actions, enable/disable workflows, and inspect durable execution history.

CRM services remain the write authorities. Workflow action handlers adapt configuration to Assignment, Metadata, Task, Note, Notification, Lead, and Audit services rather than duplicating their business logic.

Production runtime details and limitations are frozen in `docs/WORKFLOW_PLATFORM_RUNTIME_CONTRACT.md`.

## Architecture Delivered

```text
Lead / Customer / Opportunity / Invoice / Payment / Marketing services
        |
        v
11 after-commit WorkflowDomainEvent types
        |
        v
RunTriggeredWorkflows (queued listener)
        |
        +-- organization + active trigger selection
        +-- idempotency + concurrency admission
        +-- nested ConditionEvaluator
        |
        v
ActionDispatcher
        |
        v
8 handlers -> existing domain services
        |
        v
WorkflowExecution + WorkflowExecutionLog
```

| Layer | Concrete files |
| --- | --- |
| Catalog | `config/workflows.php` |
| Definition service | `app/Services/WorkflowService.php` |
| Runtime | `app/Listeners/RunTriggeredWorkflows.php`, `app/Workflow/ConditionEvaluator.php`, `app/Workflow/ActionDispatcher.php` |
| Runtime context | `app/Workflow/ActionContext.php`, `app/Workflow/WorkflowRuntimeContext.php` |
| Action contract/handlers | `app/Workflow/Contracts/WorkflowActionHandler.php`, `app/Workflow/Actions/*` |
| Events | `app/Events/WorkflowDomainEvent.php` and 11 concrete workflow event classes |
| Persistence | five `app/Models/Workflow*.php` models and `database/migrations/2026_07_18_000001_create_workflow_foundation_tables.php` |
| HTTP/RBAC | workflow controllers, requests, policies, routes, `config/rbac.php` |
| UI | `resources/views/workflows/*`, `resources/js/workflow.js`, `resources/css/workflow.css` |
| Registration | `app/Providers/AppServiceProvider.php` |

## Deliverable Mapping

### Workflow schema and models

**Delivered**

- Organization-scoped workflows, nested conditions, ordered actions, executions, and chronological logs
- Composite tenant foreign keys and indexes
- Workflow draft/active/disabled lifecycle and soft deletion
- Execution statuses, idempotency/lease fields, snapshots, payloads, outcomes, and errors
- Model factories for workflow definition and execution testing

**Files**

- `database/migrations/2026_07_18_000001_create_workflow_foundation_tables.php`
- `app/Models/Workflow.php`
- `app/Models/WorkflowCondition.php`
- `app/Models/WorkflowAction.php`
- `app/Models/WorkflowExecution.php`
- `app/Models/WorkflowExecutionLog.php`
- `database/factories/WorkflowFactory.php`
- `database/factories/WorkflowConditionFactory.php`
- `database/factories/WorkflowActionFactory.php`
- `database/factories/WorkflowExecutionFactory.php`
- `database/factories/WorkflowExecutionLogFactory.php`

**Evidence**

- `WorkflowFoundationTest::test_service_persists_nested_definition_and_controls_lifecycle`
- `WorkflowFoundationTest::test_tenant_scope_is_applied_to_all_workflow_models`
- `WorkflowUiTest::test_owner_can_create_update_enable_disable_and_delete_nested_workflow`

### Trigger catalog and CRM integration

**Delivered**

- 11 stable trigger keys: Lead create/update/assignment/conversion, Customer create/update, Opportunity create/stage change, Invoice create, Payment receipt, and Marketing lead import
- Immutable scalar event envelope with organization, subject identity/snapshot, payload, event ID, causation, and depth
- After-commit dispatch contract
- Central registration of all workflow events to one queued listener
- Emission from domain services rather than workflow controllers

**Files**

- `config/workflows.php`
- `app/Events/WorkflowDomainEvent.php`
- `app/Events/LeadCreated.php`
- `app/Events/LeadUpdated.php`
- `app/Events/LeadAssigned.php`
- `app/Events/LeadConverted.php`
- `app/Events/CustomerCreated.php`
- `app/Events/CustomerUpdated.php`
- `app/Events/OpportunityCreated.php`
- `app/Events/OpportunityStageChanged.php`
- `app/Events/InvoiceCreated.php`
- `app/Events/PaymentReceived.php`
- `app/Events/MarketingLeadImported.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/LeadService.php`
- `app/Services/LeadConversionService.php`
- `app/Services/CustomerService.php`
- `app/Services/OpportunityService.php`
- `app/Services/InvoiceService.php`
- `app/Services/PaymentService.php`
- `app/Services/Assignment/AssignmentService.php`
- `app/Services/MarketingProviderService.php`

**Evidence**

- `WorkflowRuntimeTest::test_event_execution_is_tenant_safe_idempotent_and_logs_action_outcome`
- `WorkflowTriggerProducerTest::test_owning_services_emit_tenant_subject_and_actor_payloads` covers ten owning service paths and verifies organization, subject snapshot, and actor payloads.
- `MetaLeadImportTest::test_first_import_creates_leads_through_lead_service` verifies the eleventh `marketing.lead_imported` producer payload.
- `WorkflowTriggerProducerTest::test_after_commit_domain_event_is_suppressed_when_transaction_rolls_back` verifies rollback suppression.

### Nested conditions and typed operators

**Delivered**

- Recursive `all` / `any` groups
- Group and leaf negation
- Dot-path snapshot access
- Empty root condition set as match-all
- 15 typed operators
- Deterministic null, boolean, numeric, date, array, and string semantics
- Validation limits for depth, direct children, total nodes, and operator value shape

**Files**

- `app/Workflow/ConditionEvaluator.php`
- `app/Models/WorkflowCondition.php`
- `app/Http/Requests/StoreWorkflowRequest.php`
- `app/Http/Requests/UpdateWorkflowRequest.php`
- `config/workflows.php`
- `resources/js/workflow.js`
- `resources/views/workflows/_condition_node.blade.php`
- `resources/views/workflows/_condition_summary.blade.php`

**Evidence**

- `WorkflowConditionEvaluatorTest::test_operators_are_deterministic` data provider covers all 15 operator keys
- `WorkflowConditionEvaluatorTest::test_nested_groups_and_dot_paths_are_entity_agnostic`
- `WorkflowUiTest::test_owner_can_create_update_enable_disable_and_delete_nested_workflow`

### Action catalog and domain-service reuse

**Delivered**

- 8 action types: automatic assignment, explicit reassignment, task, activity, note, Lead status, metadata update, and in-app notification
- Config-driven handler registration
- Runtime entity compatibility and required-configuration checks
- Sequential active-action dispatch with outcomes
- Existing domain services as write authorities

**Files**

- `config/workflows.php`
- `app/Workflow/ActionDispatcher.php`
- `app/Workflow/Contracts/WorkflowActionHandler.php`
- `app/Workflow/Actions/AssignOwnerAction.php`
- `app/Workflow/Actions/ReassignOwnerAction.php`
- `app/Workflow/Actions/CreateTaskAction.php`
- `app/Workflow/Actions/CreateActivityAction.php`
- `app/Workflow/Actions/AddNoteAction.php`
- `app/Workflow/Actions/ChangeLeadStatusAction.php`
- `app/Workflow/Actions/UpdateMetadataAction.php`
- `app/Workflow/Actions/NotifyUserAction.php`
- `app/Services/ActivityService.php`
- `app/Services/NoteService.php`
- `app/Services/NotificationService.php`
- `app/Services/TaskService.php`

**Evidence**

- `WorkflowRuntimeTest::test_event_execution_is_tenant_safe_idempotent_and_logs_action_outcome` executes `create_activity` and checks its Audit Platform side effect and action log.
- `WorkflowUiTest::test_owner_can_create_update_enable_disable_and_delete_nested_workflow` persists and presents `create_task` and `notify_user` definitions.

**QA note**

The focused Phase 9.2 tests do not execute every action handler end to end. All handlers were source-inspected for delegation and catalog compatibility; broad handler-by-handler runtime coverage remains a recommended follow-up.

### Queue execution, idempotency, concurrency, and recursion

**Delivered**

- Queued listener with 100 queue attempts and 5/15/30-second backoff
- Event/workflow idempotency key and unique database constraint; the first delivery captures the immutable workflow version
- Workflow-row-locked concurrency admission with pending deferral and stale-lease recovery on delivery
- Tenant context setup/restoration
- Trigger-time snapshot conditions and live tenant-owned action subject
- Per-condition/action/execution logs; retries reconstruct completed outcomes from logs
- Causation/depth propagation for implemented workflow-generated Lead update/assignment and Lead/Customer metadata update paths
- Configured maximum recursion depth

**Files**

- `app/Listeners/RunTriggeredWorkflows.php`
- `app/Events/WorkflowDomainEvent.php`
- `app/Workflow/WorkflowRuntimeContext.php`
- `app/Workflow/Actions/UpdateMetadataAction.php`
- `app/Services/LeadService.php`
- `app/Services/Assignment/AssignmentService.php`
- `database/migrations/2026_07_18_000001_create_workflow_foundation_tables.php`

**Evidence**

- `WorkflowRuntimeTest::test_event_execution_is_tenant_safe_idempotent_and_logs_action_outcome`
- `WorkflowRuntimeTest::test_concurrency_limit_is_checked_under_workflow_lock`

**Operational truth**

- Concurrency overflow remains `pending`; redelivery acquires a lease after capacity frees.
- Each local action side effect and its `action.completed` marker share one transaction and form the action idempotency boundary.
- Business failures may attempt a durable execution three times; queue-level tries are high enough not to exhaust during a valid 300-second run.
- Stale leases are reclaimed during admission, including stale same-event leases. No scheduled sweeper is included.
- The listener timeout is 330 seconds. Database and Redis `retry_after` default to 390 seconds; workers must use at least 330 seconds and remain below `retry_after`.
- Recursion is depth-limited, not cycle-detected.

### Workflow definition lifecycle and audit

**Delivered**

- Transactional create/update/enable/disable/delete service
- Version increment on every update with complete immutable condition/action copies retained for prior execution versions
- Enable guard requiring an active action
- Definition audit events for create, update, enable, disable, and delete

**Files**

- `app/Services/WorkflowService.php`
- `app/Models/Workflow.php`
- `app/Services/AuditLogger.php`

**Evidence**

- `WorkflowFoundationTest::test_service_persists_nested_definition_and_controls_lifecycle`
- `WorkflowUiTest::test_owner_can_create_update_enable_disable_and_delete_nested_workflow`
- Source inspection confirms all five `AuditLogger` calls.

### RBAC and tenant isolation

**Delivered**

- `workflows.view`, `workflows.create`, `workflows.update`, `workflows.delete`, `workflows.manage`
- Organization Owner and Manager grants
- Additive permission/grant synchronization for existing organizations
- Workflow and execution policies
- Tenant-restricted route binding and scoped execution routes
- Tenant-scoped models and runtime subject lookup

**Files**

- `config/rbac.php`
- `database/migrations/2026_07_18_000002_sync_workflow_permissions.php`
- `app/Policies/WorkflowPolicy.php`
- `app/Policies/WorkflowExecutionPolicy.php`
- `app/Models/Workflow.php`
- `routes/web.php`
- `app/Providers/AppServiceProvider.php`

**Evidence**

- `WorkflowUiTest::test_workflow_routes_enforce_rbac`
- `WorkflowUiTest::test_workflow_binding_is_tenant_isolated`
- `WorkflowUiTest::test_execution_history_and_detail_require_view_permission_and_tenant_match`
- `WorkflowRuntimeTest::test_event_execution_is_tenant_safe_idempotent_and_logs_action_outcome`

### Workflow builder and execution operations

**Delivered**

- Search/filter/list workflow administration
- Create/edit nested condition builder using compact JSON payloads and a completeness marker
- Ordered action builder with catalog-driven fields, safe key-value serialization, and entity compatibility
- Phase 9.2 rejects non-empty trigger configuration and directs filtering to conditions
- Definition detail and lifecycle controls
- Execution status summary/history/detail
- Chronological log, snapshot, payload, result, error, and idempotency display
- Sidebar integration and compiled workflow assets

**Files**

- `app/Http/Controllers/WorkflowController.php`
- `app/Http/Controllers/WorkflowExecutionController.php`
- `app/Http/Requests/StoreWorkflowRequest.php`
- `app/Http/Requests/UpdateWorkflowRequest.php`
- `resources/views/workflows/index.blade.php`
- `resources/views/workflows/create.blade.php`
- `resources/views/workflows/edit.blade.php`
- `resources/views/workflows/show.blade.php`
- `resources/views/workflows/_form.blade.php`
- `resources/views/workflows/_condition_node.blade.php`
- `resources/views/workflows/_condition_summary.blade.php`
- `resources/views/workflows/executions/index.blade.php`
- `resources/views/workflows/executions/show.blade.php`
- `resources/js/workflow.js`
- `resources/css/workflow.css`
- `resources/views/layouts/sidebar.blade.php`
- `routes/web.php`
- `vite.config.js`

**Evidence**

- All four `WorkflowUiTest` cases
- Focused command completed with rendered route assertions and no failures.

## Acceptance Criteria Matrix

| Acceptance criterion | Status | Evidence |
| --- | --- | --- |
| Organization-scoped workflow definitions and execution records | **PASS** | Schema/models; Foundation tenant test; UI binding test |
| Draft, enable, disable, update/version, and soft-delete lifecycle | **PASS** | `WorkflowService`; Foundation and UI lifecycle tests |
| Supported domain events trigger active workflows asynchronously | **PASS with coverage note** | Queued listener and 11 event registrations inspected; Lead-created path executed in Runtime test |
| Nested all/any conditions, dot paths, and negation supported | **PASS** | Evaluator/request/builder; nested evaluator and UI tests |
| All documented operators have deterministic semantics | **PASS** | 15-key operator data provider passed |
| Actions execute sequentially through reusable domain services | **PASS with coverage note** | Dispatcher/handlers inspected; activity executed; task/notification definitions tested; not all handlers executed |
| Duplicate delivery does not create a duplicate execution | **PASS** | Runtime idempotency test invokes the same event twice and asserts one execution |
| Workflow concurrency limit is checked safely | **PASS** | Workflow row lock implementation and focused concurrency test |
| Recursion has causation/depth protection | **PASS with limitation** | Runtime context and propagation paths inspected; no dedicated recursion integration test |
| Runtime restores tenant context and rejects cross-tenant subjects | **PASS** | Runtime tenant test, scoped subject query, UI tenant tests |
| Workflow administration and execution history are RBAC-protected | **PASS** | Policies, permissions, migration, four UI tests |
| Definition mutations are audited and executions are inspectable | **PASS with coverage note** | Audit calls inspected; action/execution logs asserted; UI execution detail tested |
| Production operation/deployment/troubleshooting is documented | **PASS** | `docs/WORKFLOW_PLATFORM_RUNTIME_CONTRACT.md` |
| Existing full application suite remains green | **PASS** | Final full suite: 852 tests, 3,307 assertions, 331.25s |

## Verification Baseline

Final release-candidate verification is confirmed:

- Full application suite: **852 passed / 3,307 assertions / 331.25s**
- Production Vite build: **60 modules transformed / 6.47s**
- Workflow migrations: all three Phase 9.2 migrations reported **Ran**
- Targeted formatting gate: all **90** added/modified PHP files identified from Git passed `vendor/bin/pint --test` with exit code 0

## Production Readiness Checklist

- [x] Durable workflow schema and forward migrations
- [x] Tenant ownership and composite tenant references
- [x] RBAC policies, permissions, and existing-role synchronization
- [x] After-commit domain events and queued listener registration
- [x] Idempotent execution admission
- [x] Row-locked concurrency admission
- [x] Nested typed conditions
- [x] Config-driven actions delegated to domain services
- [x] Definition audit and execution logs
- [x] Administration and execution-history UI
- [x] Focused workflow tests passing
- [x] Runtime/deployment/monitoring contract documented
- [x] Full suite run for release candidate — 852 passed / 3,307 assertions / 331.25s
- [x] Frontend production build verified for release candidate — 60 modules / 6.47s
- [x] All three workflow migrations reported Ran
- [x] Targeted Pint gate passed for all 90 added/modified PHP files
- [ ] Production queue backend and supervised worker verified in target environment
- [ ] Alerts/dashboards for queue lag, failures, pending concurrency deferrals, and stale running executions configured externally

## Known Risks And Limitations

1. **Partial completion** — earlier actions remain committed when a later action fails; their completion logs prevent duplicate local action execution on retry.
2. **Recovery is delivery-driven** — stale leases are reclaimed when a delivery performs admission; there is no scheduled sweeper.
3. **Between-action deadline** — the 300-second application deadline is checked between actions, while the 330-second queue worker timeout remains the process boundary.
4. **No external exactly-once guarantee** — future remote actions require owning-service/provider idempotency or an outbox/inbox protocol.
5. **Trigger config reserved** — Phase 9.2 rejects non-empty `trigger_config`; conditions provide filtering.
6. **Depth rather than graph cycle detection** — only propagation-aware event paths participate in the current recursion guard.
7. **No delayed/scheduled actions** — Phase 9.2 does not execute actions on a delay or schedule.

These limitations are documented behavior, not hidden production guarantees.

## What Did Not Change

- Metadata remains the authority for dynamic value normalization/projection.
- Assignment remains the authority for owner resolution and history.
- Marketing Provider remains the authority for provider imports.
- Lead, Customer, Opportunity, Invoice, and Payment services remain their entity write authorities.
- Import, Revenue, Audit, Notification, Task, and Note behavior is reused rather than reimplemented.
- No destructive database operation or data reset is required.

## Out Of Scope / Deferred

- Scheduled and delayed workflows
- Manual run/replay/retry/cancel
- Compensation and external-provider exactly-once delivery
- Scheduled stale-lease sweepers
- Parallel action branches and approvals
- Arbitrary HTTP/email/code/SQL actions
- Secret management for action configuration
- Trigger-configuration adapters
- Workflow templates, sharing, clone/import/export
- Visual graph editor
- Metrics exporter, retention jobs, and execution-log redaction policy
- Full catalog combination testing in this phase report

## Files Added By Documentation Task

- `docs/WORKFLOW_PLATFORM_RUNTIME_CONTRACT.md`
- `docs/P9_PHASE_9_2_IMPACT_REPORT.md`

`docs/NEXT_PHASE_PROMPT.md` was not modified.
