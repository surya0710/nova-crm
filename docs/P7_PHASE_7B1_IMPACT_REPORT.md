# P7 Marketing Attribution Platform - Phase 7B.1 Impact Report

## Phase

Phase 7B.1 - Tracking Foundation

## What Changed?

- Added three additive migrations creating the tracking foundation tables:
  - `marketing_visitors` — anonymous browser identities with nullable tenant ownership.
  - `marketing_sessions` — browsing sessions belonging to a visitor.
  - `marketing_touches` — marketing touchpoints belonging to a session.
- Added three Eloquent models with standard relationships and no business logic:
  - `MarketingVisitor` (hasMany sessions)
  - `MarketingSession` (belongsTo visitor, hasMany touches)
  - `MarketingTouch` (belongsTo session)
- Added `MarketingTrackingService` as the single write authority for tracking infrastructure:
  - `createVisitor`, `findVisitor`, `updateLastSeen`
  - `createSession`, `findSession`, `activeSessionFor`, `closeSession`
  - `createTouch`
- Added factories for all three models.
- Added `tests/Feature/MarketingTrackingTest.php` (15 tests, 48 assertions) covering visitor create/lookup/last-seen, session create/close/active-lookup, touch creation, relationship integrity, foreign keys, cascade behavior, unique constraints, index presence, and a CRM regression check.
- No controllers, no routes, no policies, no UI, no APIs, no JavaScript, no provider logic, and no attribution logic were added.

## Tenant Strategy (Documented Reasoning)

- `marketing_visitors.organization_id` is **nullable by design**. An anonymous visitor exists before any lead is submitted, so there may be no tenant association at capture time. Ownership is resolved at attribution time in Phase 7B.4.
- The tracking models deliberately do **not** use the `BelongsToOrganization` trait:
  - Its `OrganizationScope` global scope would hide unowned (`organization_id IS NULL`) visitors from every query.
  - Its `creating` hook would force-assign the current tenant context, which is wrong for pre-attribution visitors and for future queue workers where the tenant scope is fail-open.
- `marketing_sessions` and `marketing_touches` carry no `organization_id` at all; they inherit ownership transitively through their visitor. This avoids denormalized tenant columns that could drift, and can be revisited in 7B.4 if attribution queries need direct tenant predicates on touches.
- The organization foreign key on visitors uses `cascadeOnDelete`, so deleting an organization removes only its owned visitors (verified by test); unowned visitors are untouched.

## Notable Implementation Decisions

- The session-to-touch relationship is named `MarketingSession::touchpoints()` because Eloquent reserves `Model::touches()` for timestamp touching; declaring a `touches()` relationship is a PHP fatal error.
- `first_seen_at`, `last_seen_at`, `started_at`, and `occurred_at` use `useCurrent()` because MySQL strict mode rejects non-null `TIMESTAMP` columns without a default.
- `createSession()` closes any still-open session for the visitor inside a transaction before opening the new one, keeping the invariant of at most one active session per visitor. `closeSession()` is idempotent.
- URL fields (`landing_page`, `referrer`) are capped at 2048 characters and user agents at 1024, per the Marketing Attribution Contract; the service truncates before writing.
- No audit logging was added, as instructed. Tracking writes are high-volume; the audit strategy for this platform is deferred to a later phase.
- IP columns are `VARCHAR(45)` to hold full IPv6 addresses.

## Indexes

- `marketing_visitors`: unique `visitor_uuid`, `organization_id` (FK index), `last_seen_at` (future retention/purge scans).
- `marketing_sessions`: unique `session_uuid`, (`visitor_id`, `ended_at`) for active-session lookup, `started_at` for time-range queries.
- `marketing_touches`: (`session_id`, `occurred_at`) for ordered touch retrieval per session, `occurred_at` for time-range queries.

## Which Future Phases Are Now Enabled?

- Phase 7B.2+ (tracking beacon endpoint and script) can now persist visitors, sessions, and touches through `MarketingTrackingService`.
- Phase 7B.4 (Lead Attribution) can resolve `visitor_id`/`session_id` at lead submission, freeze first/last touch references, and assign visitor ownership (`organization_id`).
- The `ChannelClassifier` from the TDS can populate `marketing_touches.channel/source/medium` at capture time; the columns already exist and are provider-agnostic.
- Future retention/archival commands (TDS Section 10) have the timestamp indexes they need.

## Did Any Architectural Assumptions Change?

- No. The TDS assumptions hold.
- One naming deviation from the TDS ER diagram: the session→touch relationship method is `touchpoints()` due to the Eloquent `touches()` reservation. Table and FK names are unchanged.
- The decision to keep `organization_id` off sessions/touches (transitive ownership) is consistent with the TDS but is now explicit; 7B.4 should confirm whether attribution-time queries need a denormalized tenant column on touches.

## Regression Verification

- `php artisan test --filter=MarketingTracking` — 15 passed (48 assertions).
- `php artisan test` (full suite) — 462 passed (1457 assertions), 0 failures.
- No CRM, Metadata Platform, or Revenue module files were modified. The regression test additionally proves lead creation writes no tracking rows.

## CTO Recommendation

Proceed to the next 7B sub-phase after review.

The next sub-phase should include:

- The public tracking beacon endpoint (per-tenant site key, aggressive throttling) writing exclusively through `MarketingTrackingService`.
- The `ChannelClassifier` with the contract's precedence rules (click ID → UTM → referrer rules → direct).
- Session windowing rules (30-minute inactivity, new source starts a new session) enforced at the ingestion layer, reusing `activeSessionFor`/`closeSession`/`createSession`.
- No attribution writes until 7B.4.
