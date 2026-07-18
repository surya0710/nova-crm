# P7 Marketing Attribution Platform - Phase 7C.8 Impact Report

## Phase

Phase 7C.8 - Provider Synchronization Runtime Foundation

## What Changed?

Implemented a reusable, provider-agnostic synchronization execution runtime. The runtime records and owns synchronization lifecycle state while provider adapters perform provider API work and return normalized results.

No offline conversion upload, campaign synchronization, scheduling, queues, retries, or background workers were added.

### Synchronization history entity

- Additive migration: `marketing_provider_sync_runs`
- Model: `App\Models\MarketingProviderSyncRun` (`BelongsToOrganization`)
- Factory: `Database\Factories\MarketingProviderSyncRunFactory`
- Relation: `MarketingProvider::syncRuns()`
- Every run records:
  - organization and provider connection
  - canonical type and direction
  - lifecycle status
  - start and finish timestamps
  - processed, succeeded, and failed totals
  - message and provider-agnostic JSON metadata
- Duration is derived from `started_at` and `finished_at`.
- Runtime APIs never delete synchronization history.

### Canonical synchronization vocabulary

Types:

- `lead_import`
- `webhook_processing`
- `asset_discovery`
- `form_sync`
- `conversion_upload`

Directions:

- `inbound`
- `outbound`

Statuses:

- `pending`
- `running`
- `completed`
- `partial`
- `failed`
- `cancelled`

The model constants and `config('marketing.providers.synchronization')` expose the same canonical sets.

### Optional provider capability

Added `App\Contracts\MarketingProviderSynchronizationInterface`.

The optional contract declares `synchronize(MarketingProvider $provider, array $options = [])`. It is intentionally separate from the frozen `MarketingProviderInterface`; no existing platform contract was modified.

Adapters that participate in the runtime:

1. implement the optional synchronization interface;
2. execute provider-specific API behavior;
3. return normalized success and record totals;
4. never create, update, or delete synchronization runs.

### MarketingProviderService remains the authority

`MarketingProviderService` now owns:

- `startSynchronization()` - creates an organization-owned running record
- `updateSynchronizationProgress()` - writes monotonic totals and merges metadata
- `finishSynchronization()` - applies a terminal state and finish timestamp
- `recordSynchronizationFailure()` - preserves progress and records failure details
- `cancelSynchronization()` - finalizes an active run as cancelled
- `synchronizationHistory()` - returns tenant/provider-scoped history
- `supportsSynchronization()` - checks the optional adapter capability
- `synchronize()` - executes the complete runtime lifecycle around an adapter

Provider results cannot directly set runtime state. The service derives `completed`, `partial`, or `failed` from the normalized result and totals. Unexpected exceptions finalize the run before being rethrown, so execution history survives failures.

## Architecture

```text
Integration / future command
        |
        v
MarketingProviderService                 single orchestration/write authority
        |
        +--> marketing_provider_sync_runs lifecycle + history
        |
        v
MarketingProviderSynchronizationInterface
        |
        v
Provider adapter                         provider API implementation only
        |
        +--> Meta / Google / LinkedIn / future providers
```

The frozen Marketing Platform remains the source of truth for tracking, attribution, and conversions. Synchronization adapters may consume that data in future phases but cannot redefine or write its contracts.

## Runtime Lifecycle

```text
startSynchronization
  -> running + started_at
  -> adapter synchronize()
  -> updateSynchronizationProgress
       -> processed / succeeded / failed / metadata
  -> completed | partial | failed
  -> finished_at
```

Alternative terminal paths:

```text
running -> cancelled
running -> unexpected exception -> failed
running + prior successes -> recorded failure -> partial
```

Progress totals are monotonic. A finished run cannot be updated or finalized again.

## Status Model

| Status | Meaning |
| --- | --- |
| `pending` | Canonical pre-execution state available to future launchers |
| `running` | Execution started and may receive progress updates |
| `completed` | Execution finished without record failures |
| `partial` | Some records succeeded and some failed |
| `failed` | Execution failed without a successful result |
| `cancelled` | Execution was explicitly cancelled |

Only `completed`, `partial`, `failed`, and `cancelled` are terminal.

## History and Multi-Tenancy

- Every run has a required `organization_id` and `marketing_provider_id`.
- `BelongsToOrganization` applies the standard tenant scope.
- History queries also constrain both organization and provider explicitly.
- The Integration Management controller resolves the provider through the current organization before requesting history.
- Cross-tenant runs are not returned or rendered.
- Disconnecting a provider does not remove its synchronization history.

## Integration Management UI

The integration detail page now includes a provider-agnostic **Synchronization History** table with:

- Type
- Direction
- Status
- Started
- Finished
- Processed
- Failed

The page adds no scheduling, retry, queue, or synchronization-trigger UI.

## Error Handling

| Condition | Runtime behavior |
| --- | --- |
| Provider reports success with no failed records | `completed` |
| Provider reports successful and failed records | `partial` |
| Provider reports failure without successes | `failed` |
| Provider throws unexpectedly | Persist `failed`, record exception class/message, rethrow |
| Failure after recorded successes | Persist `partial` |
| Explicit cancellation | Persist `cancelled` |
| Invalid type/direction/status | Reject before invalid history is written |
| Progress decreases | Reject update; retain prior totals |

## Extension Points

Future provider capabilities reuse the same runtime:

| Future operation | Type | Direction |
| --- | --- | --- |
| Lead import | `lead_import` | `inbound` |
| Webhook processing | `webhook_processing` | `inbound` |
| Asset refresh/discovery | `asset_discovery` | `inbound` |
| Lead form synchronization | `form_sync` | `inbound` |
| Offline conversion upload | `conversion_upload` | `outbound` |

Future providers implement the optional interface and normalized execution result only. They do not create provider-specific run tables or lifecycle services.

## What Did Not Change

- Frozen `MarketingProviderInterface`
- Frozen Marketing Platform contracts and runtime services
- CRM, Revenue, and Metadata Platform behavior
- Existing Meta OAuth, asset discovery, form sync, lead import, and webhook contracts
- No Meta offline conversion implementation
- No campaign or audience synchronization
- No schedules, queues, workers, retries, or reporting

## Testing Summary

New suite: `tests/Feature/MarketingProviderSynchronizationRuntimeTest.php`

Coverage:

- canonical types, directions, and statuses
- run creation and timestamps
- monotonic progress updates
- completion and duration
- partial completion
- provider-reported failures
- unexpected exception persistence
- cancellation
- immutable terminal lifecycle
- history persistence
- organization isolation
- tenant-isolated Integration Management history

Verification:

- Runtime suite: **8 passed (50 assertions)**
- Provider + Meta + Integration suite: **111 passed (547 assertions)**
- Marketing suite: **140 passed (582 assertions)**
- Full suite: **653 passed (2328 assertions)**
- Formatting: Pint passed
- Failures: **0**

Quality gate delta from Phase 7C.7: **+8 tests, +50 assertions**.

## Completion

The Provider Synchronization Runtime Foundation is complete. Synchronization execution and history are independent of Meta, lifecycle state remains exclusively owned by `MarketingProviderService`, and future provider synchronization can reuse the optional runtime interface without modifying frozen platform contracts.

Do not begin Meta Offline Conversion Uploads without a separate implementation phase.
