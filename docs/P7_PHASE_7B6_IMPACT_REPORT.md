# P7 Marketing Attribution Platform - Phase 7B.6 Impact Report

## Phase

Phase 7B.6 - Historical Attribution Backfill

## What Changed?

- Added `MarketingBackfillService`: maintenance-only orchestrator that locates historical CRM entities, resolves deterministic visitor signals, and writes exclusively through `MarketingAttributionService` and `MarketingConversionService`.
- Added Artisan command `marketing:backfill-attribution` with `--organization`, `--lead`, `--customer`, `--opportunity`, `--dry-run`, `--chunk`, and `--force`.
- Added `config/marketing.php` `backfill` section (chunk size, signal field keys, cursor TTL).
- Added `tests/Feature/MarketingBackfillTest.php` (13 tests, 58 assertions).
- No runtime request pipeline changes. Tracking, classification, attribution, and conversion services are unchanged in behavior.

## Architecture

```
php artisan marketing:backfill-attribution
        ↓
MarketingBackfillService          ← orchestration only (no direct table writes)
        ↓
MarketingAttributionService       ← sole attribution write authority
        ↓
MarketingConversionService        ← sole conversion write authority
```

Backfill is not part of request processing. It never inserts into `marketing_attributions` or `marketing_conversions` directly.

## Matching Rules

Deterministic only — no email, IP, fingerprint, name, or AI matching.

| Priority | Signal | Behavior |
| --- | --- | --- |
| 1 | `visitor_uuid` | Read from lead `custom_fields[visitor_uuid]` (configurable key). Visitor must exist and be unowned or owned by the lead's organization. |
| 2 | `session_uuid` | Read from lead `custom_fields[session_uuid]`. Resolve session → visitor, then attribute. |
| 3 | Existing attribution links | For customers/opportunities: if the linked lead already has attribution, propagate FKs via `propagateToConversion` and replay conversions. |

Leads without resolvable signals are skipped (not failed). Cross-tenant visitor ownership blocks matching.

## Safety Guarantees

- Existing attribution is never overwritten.
- `MarketingTouch`, `MarketingSession`, and visitor tracking fields are never rewritten by the backfill service.
- Visitor `organization_id` claim happens only inside `MarketingAttributionService` during attribution (relationship establishment), not via ad-hoc backfill updates.
- Conversion replay is idempotent (duplicate prevention in `MarketingConversionService`).
- Dry-run performs zero writes (no attribution, no conversions, no visitor ownership claim, no cursor advance).
- Bulk runs are organization-scoped; single-entity options additionally constrain by organization when provided.

## Command Usage

```bash
# Preview for one organization
php artisan marketing:backfill-attribution --organization=1 --dry-run

# Live backfill (chunked, resumable)
php artisan marketing:backfill-attribution --organization=1 --chunk=100

# Single lead
php artisan marketing:backfill-attribution --organization=1 --lead=42

# Reset cursors and replay missing conversions for already-attributed entities
php artisan marketing:backfill-attribution --organization=1 --force
```

Progress metrics printed: `processed`, `skipped`, `attributed`, `conversions_replayed`, `failed`, plus dry-run `would_attribute` / `would_replay`.

## Dry-Run Behavior

- Evaluates eligibility and matching.
- Increments `would_attribute` / `would_replay` only.
- Does not call attribution/conversion write paths.
- Does not advance resumable cursors.
- Does not claim visitor ownership.

## Resumable Execution

- Per-organization, per-entity-type cursors stored in cache: `marketing:backfill:cursor:{orgId}:{leads|customers|opportunities}`.
- Bulk runs process `id > cursor` in ascending order, advancing the cursor after each chunk.
- Re-running the command continues from the last cursor.
- `--force` resets all three cursors for the organization before processing.
- Single-entity runs (`--lead` / `--customer` / `--opportunity`) ignore cursors.

## Conversion Replay

After a successful attribution (or when propagating via an existing lead attribution link), the service replays missing canonical events:

- `lead_created`
- `lead_converted` / `customer_created` / `opportunity_created` when a customer (and optional opportunity) exists
- `opportunity_won` when the opportunity is `closed_won`

Replay uses `MarketingConversionService` only; duplicates return the existing row.

## Testing Summary

- `php artisan test --filter=Marketing` — 95 passed (372 assertions): prior 7B.1–7B.5 suites green plus 13 new backfill tests covering visitor/session matching, overwrite prevention, dry-run, chunk/resume, force replay, conversion replay for pre-converted leads, customer link propagation, cross-tenant rejection, org scoping, Artisan command, signal-less skip, and tracking regression.
- `php artisan test` (full suite) — 542 passed (1781 assertions), 0 failures. Baseline 529 fully green plus 13 new tests.

## Performance Considerations

- Chunked queries (`ORDER BY id LIMIT chunk`) keep memory bounded for large tenants.
- Each lead attribution path is: signal parse → visitor/session lookup → attribution service → conversion replay (existence checks + conditional inserts).
- Cache cursors avoid re-scanning already processed IDs on resume.
- Dry-run avoids all writes and cursor updates, making large previews safe.

## Operator Prerequisite

Historical leads must carry deterministic signals in `custom_fields.visitor_uuid` and/or `custom_fields.session_uuid` (keys configurable). Without those signals, leads are skipped. Customers/opportunities can still be linked when their lead already has attribution.

## Did Any Architectural Assumptions Change?

- No. Backfill remains a maintenance capability outside the runtime pipeline. Platform services remain the only write authorities. Tracking history stays immutable from the backfill layer's perspective.

## Explicitly Deferred

- Heuristic / email / fingerprint / AI matching
- Campaign rebuilding
- Reporting / dashboards
- Provider offline conversion uploads
- Phase 7C Meta Business Integration

## CTO Recommendation

Phase 7B (Marketing Tracking Foundation through Historical Backfill) is complete. Proceed to Phase 7C (Meta Business Integration) after review, consuming attribution + conversion events as the canonical marketing truth without bypassing these services.
