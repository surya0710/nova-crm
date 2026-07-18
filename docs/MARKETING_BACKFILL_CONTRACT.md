# Marketing Backfill Contract

## Status

- Phase: P7B.F (Foundation Freeze)
- State: **Frozen**
- Companion documents: `docs/MARKETING_ATTRIBUTION_RUNTIME_CONTRACT.md`, `docs/MARKETING_CONVERSION_CONTRACT.md`
- Implementation reference: Phase 7B.6 (Historical Attribution Backfill)

## Purpose

This contract freezes the maintenance-only path that attaches historical CRM entities to marketing identity and replays missing conversion events. Backfill is **not** part of the request pipeline.

`MarketingBackfillService` orchestrates only. All writes go through `MarketingAttributionService` and `MarketingConversionService`.

## Architecture Boundary

```
php artisan marketing:backfill-attribution
        ↓
MarketingBackfillService          ← orchestration only
        ↓
MarketingAttributionService       ← sole attribution writes
        ↓
MarketingConversionService        ← sole conversion writes
```

Rules:

- Backfill never inserts into `marketing_attributions` or `marketing_conversions` directly.
- Backfill never mutates `MarketingTouch`, `MarketingSession`, or visitor tracking fields (IP, UA, timestamps) directly.
- Visitor `organization_id` claim happens only inside `MarketingAttributionService` during attribution.

## Deterministic Matching

Matching is deterministic only. **No** email, IP, fingerprint, name, fuzzy, or AI matching.

| Priority | Signal | Behavior |
| --- | --- | --- |
| 1 | `visitor_uuid` | Read from lead `custom_fields[visitor_uuid]` (configurable key). Visitor must exist and be unowned or owned by the lead's organization. |
| 2 | `session_uuid` | Read from lead `custom_fields[session_uuid]`. Resolve session → visitor, then attribute. |
| 3 | Existing attribution links | For customers/opportunities: if the linked lead already has attribution, propagate FKs via `propagateToConversion` and replay conversions. |

Rules:

- Leads without resolvable signals are **skipped** (not failed).
- Cross-tenant visitor ownership blocks matching.
- Signal field keys are config-driven: `config('marketing.backfill.visitor_uuid_field')`, `session_uuid_field`.

## Dry-Run Guarantees

When `--dry-run` / `dry_run: true`:

| Action | Allowed? |
| --- | --- |
| Evaluate eligibility and matching | Yes |
| Increment `would_attribute` / `would_replay` | Yes |
| Call attribution write paths | **No** |
| Call conversion write paths | **No** |
| Claim visitor ownership | **No** |
| Advance resumable cursors | **No** |

Dry-run must be safe for large tenant previews.

## Resumable Execution

- Per-organization, per-entity-type cursors in cache:  
  `marketing:backfill:cursor:{orgId}:{leads|customers|opportunities}`
- Bulk runs process `id > cursor` ascending, advancing after each chunk.
- Re-running continues from the last cursor.
- `--force` resets all three cursors for the organization before processing.
- Single-entity runs (`--lead` / `--customer` / `--opportunity`) ignore cursors.
- Cursor TTL: `config('marketing.backfill.cursor_ttl_seconds')` (default 7 days).
- Default chunk size: `config('marketing.backfill.chunk_size')` (default 100).

## Safety Rules

1. **Never overwrite** existing attribution.
2. **Never rewrite** touch / session / visitor tracking history.
3. **Idempotent conversion replay** — duplicate prevention in `MarketingConversionService` returns existing rows.
4. **Organization scoping** — bulk runs require `organization_id`; single-entity options additionally constrain by organization when provided.
5. **CRM non-blocking semantics preserved** — attribution refusal / skip must not invent CRM side effects.
6. **Maintenance-only** — not invoked from HTTP request handlers.

## Conversion Replay

After successful attribution (or when propagating via an existing lead attribution link), replay missing canonical events through `MarketingConversionService` only:

- `lead_created`
- `lead_converted` / `customer_created` / `opportunity_created` when a customer (and optional opportunity) exists
- `opportunity_won` when the opportunity is `closed_won`

`--force` resets cursors and replays missing conversions for already-attributed entities (still without overwriting attribution).

## Command Contract

```
php artisan marketing:backfill-attribution
    {--organization=}
    {--lead=}
    {--customer=}
    {--opportunity=}
    {--dry-run}
    {--chunk=}
    {--force}
```

Progress metrics:

| Metric | Meaning |
| --- | --- |
| `processed` | Entities examined |
| `skipped` | No signal / ineligible |
| `attributed` | New attribution created |
| `conversions_replayed` | Conversion writes attempted/completed via service |
| `failed` | Unexpected failures |
| `would_attribute` | Dry-run only |
| `would_replay` | Dry-run only |

Exit code: failure if `failed > 0`.

## Operator Prerequisite

Historical leads must carry deterministic signals in `custom_fields.visitor_uuid` and/or `custom_fields.session_uuid` (keys configurable). Without those signals, leads are skipped. Customers/opportunities can still be linked when their lead already has attribution.

## Extension Rules

Allowed:

- Config changes to chunk size, cursor TTL, signal field keys.
- Additional deterministic signal keys via config + contract revision.
- Additional replay events when the Conversion Contract adds event names.

Prohibited:

- Heuristic / probabilistic / AI identity matching without a new contract.
- Direct table writes bypassing attribution/conversion services.
- Overwriting attribution or mutating touch history.
- Invoking backfill from the public tracking or lead-intake request path.

## Non-Responsibilities

- Campaign rebuilding.
- Provider offline conversion uploads.
- Reporting / dashboards.
- Live request-time attribution (that is Attribution Runtime).
