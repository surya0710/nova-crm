# Phase 9.2 Sprint Progress — Workflow Automation Platform

## Sprint outcome

Phase 9.2 delivered Konnect Nex's first production-grade workflow automation platform.

The platform is an orchestration layer that decides:

- when a workflow should run;
- whether its configured conditions match;
- which existing platform service should perform each action.

Business logic remains in the platform that owns it. The workflow runtime does not directly implement assignment, metadata, task, activity, note, lead lifecycle, or notification rules.

## Architecture delivered

```text
Domain event
    ↓
Queued workflow listener
    ↓
Tenant and trigger resolution
    ↓
Idempotent execution lease
    ↓
Nested condition evaluation
    ↓
Sequential action dispatch
    ↓
Owning Konnect Nex services
    ↓
Execution logs and audit trail
```

The implementation follows Konnect Nex's established flow:

```text
Controllers → Form Requests → Services → Models
```

No repository pattern, CQRS, DDD layer, event sourcing, generic base service, service locator, or generic form builder was introduced.

## Phase 9.2.1 — Foundation

### Database

Added additive, tenant-owned tables:

- `workflows`
- `workflow_conditions`
- `workflow_actions`
- `workflow_executions`
- `workflow_execution_logs`

The schema includes:

- organization ownership and tenant-focused indexes;
- workflow status, version, concurrency, and timeout settings;
- nested condition relationships;
- ordered action definitions;
- immutable trigger snapshots and payloads;
- execution idempotency keys;
- leases, heartbeats, attempts, and action progress;
- detailed condition, action, failure, and completion logs;
- soft-deleted, versioned condition and action definitions so execution history remains valid after edits.

Added migrations:

- `2026_07_18_000001_create_workflow_foundation_tables`
- `2026_07_18_000002_sync_workflow_permissions`
- `2026_07_18_000003_stabilize_workflow_definition_history`

All three migrations are applied.

### Models

Added:

- `Workflow`
- `WorkflowCondition`
- `WorkflowAction`
- `WorkflowExecution`
- `WorkflowExecutionLog`

The models use Konnect Nex's `BelongsToOrganization` convention, typed casts, explicit fillable fields, relationships, status constants, factories, and tenant-aware route binding.

### Workflow configuration service

Added `WorkflowService` as the workflow definition write authority.

It supports:

- create;
- update;
- enable;
- disable;
- delete;
- nested condition persistence;
- ordered action persistence;
- immutable definition versioning;
- partial-update definition carry-forward;
- server-side trigger, condition, action, tenant, metadata, and configuration validation;
- semantic audit records for every lifecycle operation.

## Phase 9.2.2 — Event registration

Added Laravel-native domain events for all requested triggers:

### Lead

- `lead.created`
- `lead.updated`
- `lead.assigned`
- `lead.converted`

### Customer

- `customer.created`
- `customer.updated`

### Opportunity

- `opportunity.created`
- `opportunity.stage_changed`

### Revenue

- `invoice.created`
- `payment.received`

### Marketing

- `marketing.lead_imported`

Events are emitted from owning service boundaries after successful persistence. Trigger snapshots include final persisted metadata where applicable.

Additional integration work included:

- quotation conversion now emits `invoice.created`;
- opportunity stage events carry the acting user;
- lead assignment events preserve assignment history and notifications;
- transaction rollbacks suppress after-commit workflow processing;
- marketing imports retain their distinct marketing trigger.

## Phase 9.2.3 — Condition engine

Added an entity-agnostic condition evaluator with:

- nested groups up to the configured maximum depth;
- `all`/AND groups;
- `any`/OR groups;
- group and leaf negation;
- dot-path field lookup;
- subject, payload, `custom_fields`, and metadata compatibility;
- deterministic handling of strings, numbers, booleans, dates, arrays, nulls, and missing values.

Implemented operators:

- Equals
- Not Equals
- Contains
- Does Not Contain
- Starts With
- Ends With
- Greater Than
- Greater Than Equal
- Less Than
- Less Than Equal
- Between
- In List
- Not In List
- Empty
- Not Empty

Each evaluated condition is recorded in the execution log.

## Phase 9.2.4 — Action engine

Added a config-driven action dispatcher and handlers for:

### Assignment

- Assign Owner
- Reassign Owner

### CRM

- Create Task
- Create Activity
- Add Note

### Lead

- Change Lead Status

### Metadata

- Update Metadata

### Notifications

- Notify User

Every action delegates to an owning service.

Because several required platform contracts did not previously exist, this sprint added narrow services and moved the relevant write authority behind them:

- `TaskService`
- `ActivityService`
- `NoteService`
- `NotificationService`
- assignment ownership methods in `AssignmentService`
- lead lifecycle update methods in `LeadService`
- customer and opportunity create/update service paths

Important behavior preserved or hardened:

- assignment history and assignment notifications;
- automatic versus reassigned assignment reasons;
- existing owners are retained when automatic assignment has no valid match;
- metadata values use the normal metadata validation and projection pipeline;
- metadata actions serialize updates with a subject row lock;
- notification recipients must belong to the organization;
- notification URLs must be safe application-relative paths;
- action side effects and completion markers are committed atomically;
- retries skip actions already completed successfully.

## Workflow runtime and queue reliability

Added `RunTriggeredWorkflows`, a queued after-commit listener.

The runtime now provides:

- organization context restoration for queue execution;
- explicit tenant constraints on workflow and subject lookups;
- one execution per workflow/event idempotency key;
- immutable workflow version selection;
- execution leases and heartbeats;
- stale lease recovery;
- configurable workflow concurrency limits;
- pending execution deferral rather than event loss;
- retry support;
- sequential action execution;
- execution timeout enforcement up to 300 seconds;
- recursion causation IDs and maximum depth protection;
- failure callbacks and diagnostic logs;
- prior action outcome reconstruction during retries.

Database and Redis queue `retry_after` defaults were raised above the workflow listener timeout. Production workers must keep their worker timeout below the configured queue `retry_after`.

Delayed actions, scheduled workflows, and scheduled retry features were not introduced.

## Phase 9.2.5 — Management UI

Added tenant-facing screens for:

- workflow list;
- workflow details;
- workflow creation;
- workflow editing;
- enable and disable operations;
- deletion;
- execution history;
- execution details and chronological logs.

The UI includes:

- trigger selection;
- nested condition builder;
- all/any groups;
- operator-aware values;
- sequential action builder;
- type-specific action configuration;
- add, remove, move up, and move down controls;
- workflow status and execution summaries;
- execution snapshots, payloads, outcomes, and errors;
- search and status/trigger filters.

The workflow screens use Bootstrap 5 with Alpine.js. Bootstrap is loaded through workflow-specific Vite entries, while the existing Alpine instance and Konnect Nex application layout are reused.

To prevent PHP `max_input_vars` truncation and dynamic key corruption, the builder submits its complete definition through validated JSON payload fields with:

- a completeness marker;
- payload size limits;
- shape validation;
- clean condition/action serialization;
- duplicate and reserved key rejection;
- safe key syntax;
- scalar, list, between, and empty-value normalization.

Advanced trigger configuration is intentionally unavailable in Phase 9.2. Trigger filtering is expressed through workflow conditions.

## RBAC, policies, and audit

Added workflow permissions to Konnect Nex's existing RBAC system:

- `workflows.view`
- `workflows.create`
- `workflows.update`
- `workflows.delete`
- `workflows.manage`

Permissions were added through a forward-only synchronization migration without removing customized role assignments.

Added:

- `WorkflowPolicy`
- `WorkflowExecutionPolicy`
- permission-gated workflow navigation;
- controller and Form Request authorization;
- tenant-safe nested workflow/execution binding.

Audit logging covers:

- workflow creation;
- workflow updates;
- workflow enablement;
- workflow disablement;
- workflow deletion;
- assignment actions and notifications;
- activity actions.

High-volume runtime detail is stored in workflow execution logs instead of overloading the tenant audit table.

## Phase 9.2.6 — Stabilization

The stabilization pass addressed:

- stale and incomplete metadata snapshots;
- missing invoice triggers from quotation conversion;
- missing opportunity actor attribution;
- duplicate or lost execution risks;
- concurrency deferral;
- stale leases;
- queue timeout/retry mismatch;
- resumable action processing;
- partial workflow update corruption;
- historical action and condition integrity;
- invalid action and metadata configuration;
- assignment notification regression;
- unsafe notification redirects;
- large workflow form truncation;
- dynamic key collisions;
- outdated runtime documentation.

The final implementation also preserves existing task, assignment, lead, customer, opportunity, invoice, payment, metadata, marketing, audit, and notification behavior.

## Automated test coverage

New and expanded tests cover:

- workflow foundation and relationships;
- workflow CRUD and lifecycle;
- RBAC and policies;
- tenant isolation;
- audit records and actors;
- all condition operators;
- nested and negated condition groups;
- trigger registration and owning-service producers;
- after-commit and rollback behavior;
- execution idempotency;
- concurrency deferral and recovery;
- stale execution leases;
- retries and completed-action deduplication;
- immutable workflow versions;
- action dispatch and service side effects;
- assignment history and notifications;
- metadata validation and projections;
- JSON builder payload validation;
- execution history authorization.

Final verification:

- **852 tests passed**
- **3,307 assertions passed**
- **331.25 seconds**
- **90 changed PHP files passed Pint**
- **Vite production build passed**
- **60 frontend modules built**
- **All three workflow migrations are applied**

No regressions remain in the automated suite.

An unauthenticated browser smoke check confirmed that the workflow route resolves through the normal authentication boundary. Authenticated visual interaction was not performed because no login credentials were supplied; workflow UI behavior is covered by feature tests.

## Documentation delivered

Added:

- `docs/WORKFLOW_PLATFORM_RUNTIME_CONTRACT.md`
- `docs/P9_PHASE_9_2_IMPACT_REPORT.md`
- `docs/P9_PHASE_9_2_SPRINT_PROGRESS.md`

The runtime contract documents architecture, extension procedures, tenant behavior, queue deployment, monitoring, troubleshooting, and future integration boundaries.

## Explicitly out of scope

The sprint did not add:

- email actions;
- SMS actions;
- WhatsApp actions;
- webhook actions;
- calendar actions;
- AI actions;
- delayed execution;
- scheduled workflows;
- workflow templates;
- visual drag-and-drop editing;
- approval workflows;
- workflow version-management UI.

These remain future phases.

## Known future boundary

All current Phase 9.2 actions are local database operations, allowing their side effects and completion markers to be committed atomically.

Future external provider actions—such as webhooks, email, Meta, or Google—must introduce owning-service idempotency keys or an outbox-style delivery boundary before they can provide equivalent retry guarantees. Provider logic must remain outside the workflow platform.

## Final status

Phase 9.2 is implementation-complete and regression-verified.

The Workflow Automation Platform now serves as Konnect Nex's central orchestration backbone while preserving the ownership and separation of existing platforms.
