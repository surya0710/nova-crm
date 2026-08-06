        # Workflow Platform Technical Design And Runtime Contract

## Status

- Phase: P9 Phase 9.2
- State: **Implemented foundation**
- Runtime catalog: `config/workflows.php`
- Persistence: `database/migrations/2026_07_18_000001_create_workflow_foundation_tables.php`
- Permission synchronization: `database/migrations/2026_07_18_000002_sync_workflow_permissions.php`

## Purpose

This document is the production contract for defining, dispatching, executing, and operating Konnect Nex workflows. It covers the implemented Phase 9.2 foundation and calls out fields that are reserved but not yet operational.

The canonical flow is:

```text
CRM write authority
        |
        v
WorkflowDomainEvent (after commit, immutable scalar snapshot)
        |
        v
Laravel queued listener: RunTriggeredWorkflows
        |
        +-- tenant + active trigger lookup
        +-- idempotent execution creation
        +-- concurrency admission
        +-- nested condition evaluation
        |
        v
ActionDispatcher -> WorkflowActionHandler -> existing domain service
        |
        v
WorkflowExecution + chronological WorkflowExecutionLog
```

## Architecture Boundaries

| Component | Responsibility |
| --- | --- |
| CRM services | Own business writes and emit domain events after successful state changes |
| `WorkflowDomainEvent` subclasses | Carry organization, subject identity, immutable subject snapshot, payload, event identity, causation, and depth |
| `RunTriggeredWorkflows` | Queue entry point, tenant restoration, workflow selection, execution lifecycle, admission, logging, and ordered action orchestration |
| `ConditionEvaluator` | Pure, entity-agnostic evaluation of snapshots |
| `ActionDispatcher` | Validate catalog registration/entity compatibility/required keys and resolve the registered handler |
| `WorkflowActionHandler` implementations | Adapt workflow configuration to an existing domain service |
| Domain services | Remain the single write authorities for assignment, tasks, notes, metadata, notifications, lead status, and audit activity |
| `WorkflowService` | Transactional workflow-definition CRUD, lifecycle transitions, version increments, and definition audit |
| Controllers / requests / policies | HTTP authorization, validation, and presentation only |

Boundary rules:

- Controllers do not execute workflows or perform action business writes.
- Events are emitted by domain services, not model observers or workflow UI code.
- Action handlers must delegate to an existing domain service. They must not duplicate validation, tenant checks, assignment rules, metadata normalization, or persistence.
- The Laravel container may resolve a handler only at the central `ActionDispatcher` composition boundary. Handlers and domain services use constructor injection; new extension code must not call `app()` or locate services dynamically.
- `config/workflows.php` is the public trigger/action/operator catalog used by request validation and the workflow builder.
- Workflow execution consumes the event snapshot for conditions but reloads the live subject for actions. Conditions therefore describe trigger-time state; actions operate on the current tenant-owned record.
- `trigger_config` is reserved for a future trigger-adapter contract. Phase 9.2 accepts only an empty object in HTTP and service definitions; users express filtering with conditions.

## Persistence Schema

### `workflows`

Tenant-owned, soft-deleted workflow definitions:

- identity: `id`, `organization_id`
- definition: `name`, `description`, `trigger_type`, JSON `trigger_config`
- lifecycle: `status` (`draft`, `active`, `disabled`), `enabled_at`, `enabled_by`
- revision: monotonically incremented `version` on `WorkflowService::update`
- controls: `concurrency_limit` (1–100 through HTTP), `execution_timeout_seconds` (1–300 through HTTP)
- provenance: `created_by`, `updated_by`, timestamps, soft-delete timestamp

Only active workflows are selected by the runtime. Enabling requires at least one active action. Every edit increments the workflow version and writes a complete immutable condition/action copy for that version. Omitted definition keys are carried forward before the effective complete definition is validated.

The runtime checks its 300-second maximum deadline between actions. The queued listener has a 330-second process timeout; workers must permit that timeout, while the queue connection `retry_after` must remain higher (390 seconds by default).

### `workflow_conditions`

An adjacency-list tree scoped by organization and workflow:

- `type`: `group` or `condition`
- `parent_condition_id`: null for a root node
- group fields: `boolean_operator` (`all` or `any`)
- leaf fields: dot-path `field`, `operator`, JSON `value`
- common fields: `negated`, `position`

Composite foreign keys prevent a condition from referencing a workflow or parent in another organization. Updates soft-delete the prior current tree and build the complete next-version tree, preserving historical rows.

### `workflow_actions`

Ordered action steps:

- tenant/workflow ownership
- catalog `type`
- optional display `name`
- JSON `configuration`
- `status` (`active`, `disabled`)
- zero-based `position`

Only active actions execute, ordered by `position`. A failure stops the sequence; later actions do not run.

### `workflow_executions`

Durable run record:

- workflow identity and captured `workflow_version`
- polymorphic trigger subject identity
- JSON trigger-time subject snapshot and payload
- status: `pending`, `running`, `completed`, `failed`, `cancelled`, or `skipped`
- idempotency key and lease fields
- attempt, current action position, queued/started/finished timestamps
- error and JSON result

The execution stores the workflow version number. Definition updates retain soft-deleted historical condition/action rows and create a complete immutable copy for the next version, allowing an execution to resolve its captured version.

### `workflow_execution_logs`

Chronological, tenant-owned events associated with an execution and optionally an action or condition:

- severity `level`
- machine-readable `event` and `status`
- optional `message`
- JSON `context`
- `occurred_at`

Action and condition foreign keys continue to resolve soft-deleted historical definition rows. They become null only if those rows are hard-deleted.

## Trigger Catalog

All triggers are `WorkflowDomainEvent` subclasses and are registered to `RunTriggeredWorkflows` in `AppServiceProvider`.

| Trigger | Subject | Emission authority / meaning |
| --- | --- | --- |
| `lead.created` | Lead | `LeadService`; web, API, import, and provider-backed lead creation paths that use the service |
| `lead.updated` | Lead | `LeadService::update`; also emitted by workflow metadata updates when values changed |
| `lead.assigned` | Lead | `AssignmentService` when the owner changes |
| `lead.converted` | Lead | `LeadConversionService` after conversion writes |
| `customer.created` | Customer | `CustomerService`; also new customer creation during lead conversion |
| `customer.updated` | Customer | `CustomerService::update`; also emitted by workflow metadata updates when values changed |
| `opportunity.created` | Opportunity | `OpportunityService`; also opportunity creation during lead conversion |
| `opportunity.stage_changed` | Opportunity | `OpportunityService` when the stage actually changes |
| `invoice.created` | Invoice | `InvoiceService` after invoice and item persistence |
| `payment.received` | Payment | `PaymentService` after payment/invoice updates |
| `marketing.lead_imported` | Lead | `MarketingProviderService` after a provider import resolves or creates the lead |

`WorkflowDomainEvent` implements `ShouldDispatchAfterCommit`. If emitted inside a database transaction, Laravel dispatches it only after commit. Each event contains:

- `organizationId`
- subject morph type and primary key
- `attributesToArray()` snapshot
- trigger-specific payload, normally including `actor_id`
- generated UUID `eventId` unless supplied by the caller
- optional `causationId`
- recursion `depth`

## Action Catalog

| Action | Supported subjects | Required configuration | Runtime authority / result |
| --- | --- | --- | --- |
| `assign_owner` | Lead, Customer, Opportunity | none | `AssignmentService::assignOwner(... automatic: true)`; owner and strategy |
| `reassign_owner` | Lead, Customer, Opportunity | `user_id` | `AssignmentService`; explicit tenant member owner |
| `create_task` | Lead, Customer, Opportunity | `title` | `TaskService::createFor`; optional description, priority, due time, assignee; task ID |
| `create_activity` | Lead, Customer, Opportunity, Invoice, Payment | `event` | `ActivityService` / `AuditLogger`; optional properties; audit log ID |
| `add_note` | Lead, Customer, Opportunity | `body` | `NoteService`; entity-specific note model; note ID |
| `change_lead_status` | Lead only | `status` | `LeadService::changeStatus`; resulting status |
| `update_metadata` | Lead, Customer, Opportunity | `values` map | `MetadataValueStorageService` and `MetadataProjectionService`; changed flag and keys |
| `notify_user` | Lead, Customer, Opportunity, Invoice, Payment | `user_id`, `title`, `message` | `NotificationService`; optional action URL; notified user ID |

The request layer rejects an action whose configured subject types do not include the selected trigger entity. The dispatcher repeats this compatibility check at runtime.

Member-valued action fields are tenant-validated at definition time and again by their domain service at execution time. This protects against membership changes between definition and execution.

Action outcomes are JSON-serializable arrays and are stored in both `action.completed` log context and the final execution result.

## Condition Tree Semantics

### Shape and limits

- No root conditions means every event with the selected trigger matches.
- Multiple root nodes use implicit `all`.
- A group uses `all` (logical AND) or `any` (logical OR).
- `negated: true` inverts the completed result of either a leaf or an entire group.
- Groups must have at least one child.
- HTTP validation allows at most 100 root nodes, 100 direct children per group, 500 total nested nodes, and `config('workflows.max_depth')` levels (10 by default).
- Sibling evaluation follows persisted `position`; evaluation does not short-circuit.

Example:

```text
ALL
├── status equals "new"
└── NOT ANY
    ├── custom_fields.score less_than 50
    └── payload.source equals "blocked"
```

### Snapshot paths

Conditions receive this logical document:

```json
{
  "...subject attributes also appear at the root...": "...",
  "subject": {"...trigger-time model attributes...": "..."},
  "payload": {"...event payload...": "..."}
}
```

Fields use Laravel dot paths (`status`, `custom_fields.region`, `subject.status`, `payload.actor_id`). A missing path resolves to `null`. Root subject attributes are convenient aliases for `subject.*`.

The snapshot is a JSON-compatible event-time representation. Relationship data is not automatically loaded into it.

### Typing and coercion

Comparison order is deterministic:

1. If either equality operand is null, only strict null-to-null equality succeeds.
2. If either equality operand is boolean, both operands are boolean-normalized.
3. If both are numeric, both compare as floats.
4. If both match the supported ISO-like date/date-time shape and parse, timestamps compare.
5. If either is an array, arrays compare after list normalization or recursive associative-key sorting.
6. Otherwise scalar/stringable values compare as exact, case-sensitive strings.

Ordering operators compare numeric pairs numerically, parseable date pairs by timestamp, and all other values with case-sensitive string ordering.

Date recognition requires `YYYY-MM-DD` optionally followed by an ISO-like time section. Runtime parsing uses `CarbonImmutable`.

`false`, `0`, and `"0"` are not empty. Empty means exactly null, empty string, or empty array.

### Operator catalog

| Operator | Contract |
| --- | --- |
| `equals` | Typed equality using the precedence above |
| `not_equals` | Negation of `equals` |
| `contains` | For an actual array, any item typed-equals expected; otherwise case-sensitive substring |
| `does_not_contain` | Negation of `contains` |
| `starts_with` | Case-sensitive string prefix |
| `ends_with` | Case-sensitive string suffix |
| `greater_than` | Numeric, date, or string ordering |
| `greater_than_equal` | Inclusive greater ordering |
| `less_than` | Numeric, date, or string ordering |
| `less_than_equal` | Inclusive lesser ordering |
| `between` | Inclusive lower/upper bounds; expected value must contain exactly two items |
| `in_list` | Actual typed-equals any expected list item |
| `not_in_list` | Negation of `in_list` |
| `empty` | Actual is null, empty string, or empty array |
| `not_empty` | Negation of `empty` |

List operators require a non-empty array through HTTP validation. The evaluator also accepts a comma-separated expected string for programmatic use. `empty` and `not_empty` normalize their stored expected value to null.

## Event, Queue, And Execution Lifecycle

1. A domain service persists business state and emits a `WorkflowDomainEvent`.
2. Commit succeeds; Laravel releases the event.
3. The queued listener is placed on the configured default queue connection.
4. The worker invokes `RunTriggeredWorkflows` with scalar event data.
5. The listener saves the prior tenant, loads the event organization, and sets `TenantContext`.
6. It selects active workflows for that organization and exact trigger.
7. For each workflow it creates one `pending` execution with `queued_at`.
8. Under a row lock on the workflow, it reclaims stale leases, counts running executions, and either admits the run or leaves it `pending` for redelivery.
9. An admitted run becomes `running`, receives a lease UUID, timestamps/heartbeat, and incremented attempt.
10. The live subject is loaded without global scopes but constrained by event organization and subject ID.
11. Conditions evaluate against the event snapshot/payload and emit `condition.evaluated` logs.
12. A non-match becomes `skipped`.
13. Active actions execute sequentially. Each action handler and its `action.completed` marker share one local database transaction; this is the idempotency boundary. `action.started` remains outside that transaction for diagnostics.
14. Success becomes `completed` with action outcomes. An exception becomes `failed`, records error context, then is rethrown.
15. Lease ownership fields are cleared and tenant/runtime context is restored in `finally`.

`RunTriggeredWorkflows::$tries` is 100 with 5/15/30-second backoff. Concurrency deferrals therefore retain a retry horizon safely beyond one valid 300-second execution. Business failures may retry the durable execution at most three times.

## Idempotency, Concurrency, Recursion, And Failure Guarantees

### Idempotency

The execution key is:

```text
SHA-256(event_id | workflow_id)
```

A unique database constraint on organization, workflow, and idempotency key plus `firstOrCreate` prevents the same event/workflow occurrence from starting twice. The execution captures the immutable workflow version on first delivery. A replay must preserve `eventId`; generating a new event ID is a new occurrence.

Retries reconstruct prior action outcomes from `action.completed` log contexts and skip those action IDs. For the current local database action catalog, each side effect and that completion marker commit atomically, so a failed attempt cannot retain one without the other.

This guarantee does not extend to future external providers: a database transaction cannot atomically commit a remote API side effect. Such actions require owning-service/provider idempotency keys or an outbox/inbox protocol and must not be described as exactly once.

### Concurrency

- Admission is serialized with a `SELECT ... FOR UPDATE` lock on the workflow row.
- Running executions are counted for the workflow.
- If the count is at or above `concurrency_limit`, the execution remains `pending` and the listener releases the queue job.
- Redelivery retries lease acquisition after capacity frees.
- Concurrency is per workflow, not per tenant, trigger subject, or action type.

### Recursion

`WorkflowRuntimeContext` carries the current event ID as causation and the current depth while actions run. Implemented workflow-generated Lead update/assignment and Lead/Customer metadata update events increment depth and retain causation.

Events with `depth > config('workflows.max_depth')` are ignored. With the default 10, depth 0 through 10 may execute; depth 11 is ignored. This is a depth guard, not cycle detection. Independent domain services that emit follow-on events without propagating runtime context start a new chain and are not protected by the prior chain depth.

### Failure and lease limitations

- One action exception fails the execution and prevents later actions.
- Completed earlier actions are not rolled back as a workflow unit.
- `action.started` and `action.failed` remain durable diagnostics outside the failed action transaction; a rolled-back action never leaves a false `action.completed` marker.
- Admission reclaims stale `running` rows whose heartbeat/lease exceeds the workflow timeout, including a stale lease for the same event. There is intentionally no scheduled sweeper; recovery occurs on delivery.
- `cancelled` is a reserved status; there is no cancellation command/UI.
- There is no dead-letter replay or resume-from-current-action operation.

## Tenant Model

- All five workflow tables carry `organization_id` and use `BelongsToOrganization`.
- Composite foreign keys prevent cross-tenant workflow/condition/action/execution relationships.
- Route binding for workflows is restricted to the current session/tenant organization.
- Execution detail uses scoped route binding, verifies workflow ownership, and applies policy authorization.
- Runtime workflow selection is explicitly restricted to the event organization.
- Subject loading bypasses global scopes only to support a queue process, then explicitly requires matching `organization_id` and primary key.
- Tenant context is set before model/action work and restored afterward, including exception paths.
- Actors and configured users must be organization members.

No workflow may read or mutate another tenant's subject, members, definition, execution, or logs.

## RBAC And Audit

Permissions:

| Permission | Capability |
| --- | --- |
| `workflows.view` | List/view definitions and execution history/detail |
| `workflows.create` | Create draft definitions |
| `workflows.update` | Edit definitions |
| `workflows.delete` | Soft-delete definitions |
| `workflows.manage` | Enable and disable definitions |

Organization owners receive all permissions. The Manager role receives all five workflow permissions. Other default roles receive none. The permission migration seeds missing permissions and attaches configured grants without detaching existing grants; rollback intentionally preserves them.

Definition audit events emitted through `AuditLogger`:

- `workflow_created`
- `workflow_updated` with before/after definition metadata
- `workflow_enabled`
- `workflow_disabled`
- `workflow_deleted`

Execution observability is stored separately in `workflow_executions` and `workflow_execution_logs`. Action-backed domain services may also create their normal domain audit records.

## UI Operations

The authenticated tenant UI provides:

- Workflows list with search, status filter, trigger filter, counts, and latest result
- Create/edit builder for trigger, nested all/any/negated conditions, and ordered actions. Compact JSON hidden fields with a completeness marker avoid `max_input_vars` truncation and bracket-key corruption.
- Action compatibility filtering and catalog-driven form fields
- Draft/active/disabled lifecycle, enable/disable, and soft delete
- Definition detail with version, controls, condition summary, action sequence, and execution counts
- Execution history with status filter, attempt, timing, duration, and error
- Execution detail with chronological logs, condition/action context, snapshot, payload, result, error, and idempotency key

There is no manual run, replay, retry, cancel, pause, scheduling, approval, or bulk-operation UI.

## Adding A Trigger

1. Add a small event class under `App\Events` extending `WorkflowDomainEvent`; `trigger()` returns a stable namespaced key.
2. Add the key, subject entity, label, and description to `config/workflows.php`.
3. Register that event class with `RunTriggeredWorkflows` in `AppServiceProvider`.
4. Emit it from the existing domain write authority after the business state is valid. Do not emit from a controller, model observer, or duplicate write path.
5. Include only JSON-safe payload data and an `actor_id` when available. The base event captures the model snapshot.
6. If emission can happen during a workflow action, constructor-inject `WorkflowRuntimeContext` into the emitting service and propagate causation/depth. Do not resolve it with `app()`.
7. Add tests for after-commit behavior where relevant, tenant selection, snapshot/payload conditions, idempotency, and recursion.

A trigger adapter must be introduced before giving `trigger_config` matching semantics. Do not embed trigger-specific filtering branches in the listener.

## Adding An Action

1. Confirm or add a single domain service that owns the business operation.
2. Add a handler implementing `WorkflowActionHandler`.
3. Constructor-inject the domain service and translate `ActionContext` plus configuration into one service call.
4. Return a small JSON-safe outcome map. Do not put business persistence or duplicated validation in the handler.
5. Register catalog metadata in `config/workflows.php`: label, description, handler class, supported entities, required keys, and builder form fields.
6. Add definition validation tests, dispatch compatibility tests, tenant/member tests, outcome/log tests, and failure behavior tests.

Do not add action-type `match` statements to `ActionDispatcher`, controllers, JavaScript, or the listener. The config registration and handler contract are the extension boundary.

## Adding An Operator

1. Add the stable key to `ConditionEvaluator::OPERATORS` and implement its semantics only in `ConditionEvaluator`.
2. Add the key to `workflows.operators`.
3. Add label and UI value shape (`single`, `between`, `list`, or `none`) to `operator_definitions`.
4. Extend request shape validation only if the operator needs a shape not already represented.
5. Add positive, negative, null/missing, coercion, nested-group, and negation unit cases.

Do not implement comparison behavior in request classes or JavaScript. They only validate/collect the value shape; `ConditionEvaluator` is the single semantic authority.

## Queue Worker And Deployment Requirements

Production must not use `QUEUE_CONNECTION=sync`; a durable queue connection is required. The repository defaults to the database queue and includes the jobs/failed-jobs migration.

Deployment checklist:

1. Back up the database according to normal release policy.
2. Deploy application and built frontend assets together.
3. Run forward migrations with `php artisan migrate --force`.
4. Confirm `QUEUE_CONNECTION` points to the intended durable backend.
5. Run long-lived queue workers under Supervisor, systemd, a container orchestrator, or equivalent:

```bash
php artisan queue:work --tries=100 --timeout=330
```

6. Keep the worker timeout at least 330 seconds and strictly below the database/Redis `retry_after` setting (390 seconds by default). Environment overrides must preserve that ordering.
7. On each deploy, restart workers so they load current event/action classes and config:

```bash
php artisan queue:restart
```

8. Ensure enough workers for expected event volume. More workers increase throughput; workflow row locking still serializes admission checks.
9. Monitor failed jobs and queue age. A listener exception is recorded as a failed workflow execution and, depending on queue configuration, as a failed queue job.

Do not configure a worker-level `--tries` lower than the listener's 100 attempts; doing so can prematurely exhaust concurrency deferrals.

## Monitoring And Troubleshooting

Monitor:

- queue depth and oldest-job age
- failed queue jobs for `RunTriggeredWorkflows`
- execution counts/rates by status and workflow
- failed execution error messages and `execution.failed` logs
- skipped executions caused by condition mismatches and pending executions awaiting concurrency
- `running` executions with stale heartbeat
- action duration inferred from chronological logs
- recursion depth in `trigger_payload._event.depth`

Common diagnoses:

| Symptom | Check |
| --- | --- |
| No execution exists | Worker running; event registered; transaction committed; workflow active; exact trigger; depth not above maximum |
| Execution skipped | Conditions did not match; concurrency overflow remains pending instead |
| Expected condition fails | Inspect trigger snapshot/payload, exact dot path, case sensitivity, null/missing behavior, and numeric/date coercion |
| Execution fails before an action | Subject still exists in the event organization; organization has an actor/member available |
| Action fails | Execution detail error plus `execution.failed`; verify member references and current domain validation |
| Execution remains running | Worker/process crash; inspect heartbeat, queue logs, and side effects before any manual replay |
| Duplicate side effect | Compare event IDs/idempotency keys; determine whether a new event ID or crash occurred after the side effect |
| Trigger configuration is rejected | Expected in Phase 9.2; use conditions until a trigger adapter contract exists |
| Execution remains pending | Inspect workflow concurrency and queue redelivery; pending means capacity was unavailable |

Never “fix” a stuck workflow by deleting execution history before checking action side effects and queue/worker logs.

## Verification Commands

Focused workflow verification:

```bash
php artisan test --filter=Workflow
```

Exact suites:

```bash
php artisan test tests/Unit/WorkflowConditionEvaluatorTest.php
php artisan test tests/Feature/WorkflowFoundationTest.php
php artisan test tests/Feature/WorkflowRuntimeTest.php
php artisan test tests/Feature/WorkflowUiTest.php
```

Optional broader regression gate:

```bash
php artisan test
```

Build verification:

```bash
npm run build
```

Final release-candidate verification is confirmed:

- Full application suite: **852 passed / 3,307 assertions / 331.25s**
- Production Vite build: **60 modules transformed / 6.47s**
- Workflow migrations: all three Phase 9.2 migrations reported **Ran**
- Targeted formatting gate: all **90** added/modified PHP files identified from Git passed `vendor/bin/pint --test` with exit code 0

## Explicitly Out Of Scope

- Scheduled/time-based, cron, delay, wait, and recurring triggers
- Webhook-defined or arbitrary user-defined triggers
- Runtime semantics or non-empty definitions for `trigger_config`
- Parallel branches, joins, approvals, human tasks, or compensating transactions
- Delayed actions, action retries, retry backoff, resume, replay, and dead-letter UI
- External-provider exactly-once side effects or provider idempotency protocols
- Scheduled stale-lease sweepers or cancellation
- Visual graph/canvas authoring; the builder is a nested form
- Template marketplace, cross-tenant sharing, import/export, cloning, or bulk controls
- Secrets/credential action fields and arbitrary code, SQL, HTTP, email, or shell actions
- Metrics exporter, alert rules, retention/archival jobs, and execution-log redaction policy
- Automatic cycle detection beyond maximum depth
- Changes to frozen Metadata, Marketing, Import, Provider, Assignment, or Revenue platform ownership contracts
