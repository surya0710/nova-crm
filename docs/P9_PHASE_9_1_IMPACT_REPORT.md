# P9 Phase 9.1 Impact Report — Assignment Platform Foundation

## Phase

Phase 9.1 — Assignment Platform Foundation (v1)

## Outcome

Konnect Nex now has a reusable **Assignment Platform** that owns owner resolution for
any entity type. Lead is the first consumer. Assignment logic exists in exactly
one place: `AssignmentService` → `AssignmentRuleEngine` → strategy → pool.

Frozen platforms (Metadata, Marketing, Provider, Import) were not redesigned.
Lead Import, API intake, and marketing provider imports reuse the same
`LeadService` → `AssignmentService` path.

## Platform Architecture

```text
LeadService / Import / API / Marketing Import
        |
        v
AssignmentService          (orchestration + history)
        |
        v
AssignmentRuleEngine       (match rule, no persistence)
        |
        v
AssignmentStrategy         (round_robin | weighted | least_loaded | manual_queue)
        |
        v
AssignmentPool + Members
        |
        v
User
        |
        v
AssignmentHistory
```

| Component | Responsibility |
| --- | --- |
| `AssignmentService` | Resolve owner, record history, detect explicit-owner bypass |
| `AssignmentRuleEngine` | Priority match, default rule fallback, strategy dispatch |
| `AssignmentContext` | Entity-agnostic attributes (source, status, country, metadata, …) |
| `AssignmentResult` | Immutable outcome (assignee, rule, pool, strategy) |
| `AssignmentConfigurationService` | Pool/rule CRUD + Audit Platform events |
| `AssignmentStrategyRegistry` | Config-driven strategy registration |

Namespace: `App\Services\Assignment\`

## Rule Engine

Rules are organization-scoped and ordered by `priority` (ascending), then `id`.

1. Load active rules for `(organization, entity_type)`
2. Evaluate non-default rules in priority order — **first match wins**
3. If none match, use the active **default rule** (if any)
4. Resolve pool (must be active) and strategy (rule override or pool strategy)
5. Execute strategy and return `AssignmentResult`

Supported conditions:

- Source
- Status
- Country
- Lead Type
- Pipeline
- Metadata field (key/value)
- Default rule flag

Empty condition maps act as catch-alls. Matching is case-insensitive for scalars.

## Strategy Implementations

| Strategy | Behavior |
| --- | --- |
| `round_robin` | Sequential active members by member id; advances `rotation_position` |
| `weighted_round_robin` | Expands members by weight into a deterministic sequence; advances position |
| `least_loaded` | Lowest open-lead workload (excludes `converted` / `won` / `lost`); ties → lowest member id |
| `manual_queue` | Always returns unassigned |

Inactive members never receive assignments. Inactive pools fail closed (matched rule, no assignee).

Weighted round robin is **not random**. Example weights Alice=5, Bob=3, Charlie=2 produce exactly that distribution every 10 assignments.

## Concurrency Model

Round robin and weighted round robin mutate shared pool state (`rotation_position`).

### Database locking approach

Each strategy assign call:

1. Opens a database transaction
2. Reloads the pool row with `SELECT … FOR UPDATE` (`lockForUpdate()`)
3. Computes the next index from the locked `rotation_position`
4. Increments `rotation_position` by 1 (absolute counter, not modulo reset)
5. Commits

### Why this prevents duplicate round-robin assignments

Two simultaneous lead creations cannot share the same rotation slot because:

1. **Row lock** — the second transaction blocks until the first commits (or rolls back) its pool row update
2. **Absolute counter** — position is always `previous + 1`, so modulo index selection never reuses a consumed slot under contention
3. **No optimistic retries** — there is no read-modify-write without a lock; the platform does not assume single-threaded callers

Therefore concurrent callers observe a serialized sequence of positions: `0, 1, 2, …` with no duplicates and no skips under successful commits.

Least-loaded and manual-queue strategies do not mutate rotation state.

## History Model

`assignment_histories` records:

- entity type / entity id
- organization
- previous owner / new owner
- strategy, rule, pool
- assigned by
- reason (`automatic`, `manual`, `reassigned`, `imported`, `api`)
- assigned_at

History is written only when the Assignment Platform path runs and a rule matched.
Explicit owners skip the platform entirely (no history row from auto-assignment).

## Lead Integration

| Path | Behavior |
| --- | --- |
| Web create | Auto-assign when `assigned_to` blank |
| Explicit `assigned_to` | Bypass Assignment Platform |
| Lead Import (owner blank) | Assignment Platform (`imported`) |
| Lead Import (owner set) | Respect imported owner |
| API intake (owner blank) | Assignment Platform (`api`) |
| Marketing / Meta / webhook import | Assignment Platform (`imported`) via `LeadService::create` |

`LeadService` asks only: “Who should own this record?” via `AssignmentService`.
No strategy/rule/pool logic lives in `LeadService`.

## Security Model

- All pools, members, rules, and histories use `BelongsToOrganization`
- Configuration service rejects cross-tenant pool references and non-member users
- Settings UI gated by `assignments.view` / `assignments.manage`
- Manager role receives both permissions; organization-owner retains `*`

## Audit

Reuses `AuditLogger` (no separate audit subsystem):

| Event | When |
| --- | --- |
| `pool_created` / `pool_updated` / `pool_disabled` | Pool configuration |
| `rule_created` / `rule_updated` / `rule_disabled` | Rule configuration |
| `assigned` (with `via: assignment_platform`) | Automatic assignment on lead create |

## Configuration UI

Settings → **Assignments**

- Assignment Pools (strategy, members, weights, activation)
- Assignment Rules (priority, conditions, default flag, pool, strategy override)

No drag-and-drop; priority is numeric.

## Testing Summary

Assignment suite covers:

- Round robin / weighted / least loaded / manual queue
- Rule priority, inactive rules/pools/members, default rule, metadata conditions
- Concurrency rotation integrity (no duplicate/skipped slots)
- Lead create, import, API, marketing import, explicit-owner bypass
- Tenant isolation for pools, rules, history
- Settings permission + pool create audit

Quality gate after Phase 9.1: run Assignment suite → CRM regression → full suite (all green).

## Future Extension Points

Out of scope for 9.1 (intentionally deferred):

- Availability / HRMS shifts / leave
- Calendar integration
- AI assignment / skill matching
- Geographic assignment
- Scheduled redistribution / auto-reassignment
- Queue workers / background balancing

Adding a new entity consumer requires:

1. Build `AssignmentContext` for that entity type
2. Call `AssignmentService` when owner is blank
3. Optionally add a `least_loaded` workload config entry

No platform core redesign required.
