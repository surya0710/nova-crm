# P7 Marketing Attribution Platform - Phase 7B.2 Impact Report

## Phase

Phase 7B.2 - Visitor Identification & Tracking Runtime

## What Changed?

- Added `config/marketing.php` with all runtime tunables (cookie names, visitor lifetime, session timeout, activity granularity, endpoint rate limit). No values are hardcoded; all are env-overridable.
- Added `MarketingTrackingMiddleware` (aliased `marketing.tracking`) that resolves the anonymous visitor and session from cookies, exposes both to the request lifecycle via request attributes, and re-issues cookies on the response. All persistence is delegated to `MarketingTrackingService`.
- Added the internal tracking endpoint `POST /marketing/track` (`MarketingTrackingController::store`) supporting the `page_view` event, validated by `TrackPageViewRequest`.
- Extended `MarketingTrackingService` (still the single write authority) with runtime methods:
  - `resolveVisitor` — find-by-cookie or create; meaningful-only last-seen updates.
  - `resolveSession` — cookie continuation, active-session fallback, inactivity timeout, automatic rollover.
  - `isSessionExpired`, `recordActivity`, `sessionTimeoutMinutes`.
  - `recordPageView` — creates the touch and records session activity.
- Added one additive migration: `last_activity_at` on `marketing_sessions` (backfilled from `started_at`, reversible). Session timeout needs an explicit activity marker; overloading `updated_at` would be fragile.
- Added the `marketing-tracking` per-IP rate limiter and excluded `marketing/track` from CSRF validation (see Security).
- Added `tests/Feature/MarketingTrackingRuntimeTest.php` (13 tests, 70 assertions).

## Runtime Architecture

```
Browser (NovaCRM-powered page)
    → POST /marketing/track  (throttle:marketing-tracking)
        → MarketingTrackingMiddleware
            reads visitor/session cookies
            → MarketingTrackingService::resolveVisitor / resolveSession
            exposes marketing_visitor / marketing_session request attributes
        → TrackPageViewRequest (payload validation)
        → MarketingTrackingController
            → MarketingTrackingService::recordPageView → MarketingTouch
        ← 204 No Content + re-issued cookies
```

- The middleware performs no database writes itself; every persistence path goes through `MarketingTrackingService`.
- The endpoint is registered in the `web` group but attached only to `POST /marketing/track`. CRM pages are not tracked (verified by regression test); attaching the middleware alias to public marketing pages is a deployment decision for later phases.
- Touches record `landing_page`, `referrer`, `occurred_at` only. Channel, source, medium, campaign, content, and term remain null until classification in Phase 7B.3.

## Cookie Lifecycle

- Two cookies, both opaque platform-issued UUIDs. No fingerprinting of any kind.
  - Visitor cookie (`nova_mk_visitor` by default): 2-year lifetime, re-issued on every tracked response so the expiry slides.
  - Session cookie (`nova_mk_session` by default): lifetime equals the session timeout (30 minutes by default), re-issued on every tracked response so the expiry slides with activity.
- Cookies are `httpOnly`, `SameSite=Lax`, `secure` per `session.secure`, and encrypted by Laravel's standard `EncryptCookies` middleware.
- An unknown or purged visitor UUID yields a fresh identity and a fresh cookie; requests never fail because of stale cookies.
- Visitor identity persists across sessions: session rollover never changes the visitor UUID (covered by tests).

## Session Lifecycle

- Continuation: a session cookie pointing at an open session belonging to the same visitor, with activity inside the timeout window, is continued.
- Timeout: sessions inactive longer than `session_timeout_minutes` are treated as expired. On the next event the expired session is closed with `ended_at` set to its last known activity, and a new session starts (rollover). The visitor is unchanged.
- Missing session cookie: the visitor's open, non-expired session is reused; otherwise a new session starts.
- One-active-session invariant from 7B.1 is preserved: `createSession` still closes any open session transactionally.
- All lifecycle rules live in `MarketingTrackingService`; the middleware and controller only orchestrate.

## Security Considerations

- CSRF: `marketing/track` is excluded from CSRF token validation. It is a beacon-style endpoint hit by anonymous browsers that hold no CSRF token. Compensating controls: per-IP throttling, strict payload validation, `SameSite=Lax` httpOnly cookies, no authenticated side effects, and a 204 response that leaks nothing.
- Rate limiting: `marketing-tracking` limiter, per-IP, default 120/minute, configurable via `MARKETING_TRACKING_RATE_LIMIT`. Throttling runs before the tracking middleware so throttled requests create no visitors.
- Malformed payloads: `TrackPageViewRequest` enforces a closed event whitelist (`page_view`), URL format and 2048-char caps on all URLs, and date validation on `occurred_at`. Invalid payloads are rejected 422 before any write.
- Client timestamps are untrusted: future `occurred_at` values are clamped to server time.
- No authentication is required or consulted; the endpoint never touches tenant context, and no `organization_id` is assigned (ownership is resolved at attribution in 7B.4).
- No fingerprinting: identity is cookie-UUID-based only. IP and user agent are stored as diagnostic context per the contract, never used as identity keys.

## Performance Considerations

- Meaningful-only writes: visitor `last_seen_at` and session `last_activity_at` are updated only when older than `activity_granularity_seconds` (default 60s). Rapid page views within the window cause zero timestamp writes (covered by test).
- A tracked page view performs at most: one visitor lookup by unique UUID, one session lookup by unique UUID (or the indexed active-session lookup), one touch insert, and up to two conditional timestamp updates.
- `last_activity_at` reads use the existing (`visitor_id`, `ended_at`) active-session index; no new indexes were needed for the runtime paths.
- No caching and no cleanup jobs, as instructed; retention remains a later phase per the TDS.

## Tests Executed

- `php artisan test --filter=MarketingTracking` — 28 passed (118 assertions): 15 from Phase 7B.1 (all still green) plus 13 new runtime tests:
  - Visitor: first visit creates visitor/session/touch with cookies; returning visitor recognized without duplication; cookie persistence re-issues the same UUID; unknown cookie yields fresh identity.
  - Session: continuation within timeout; rollover after inactivity (old session closed, visitor preserved, new session cookie issued); missing session cookie reuses the active session; meaningful-only activity updates.
  - Tracking: page_view creates a touch with context and null channel fields; future timestamps clamped; invalid payloads rejected 422 with no writes; rate limiting returns 429.
  - Regression: authenticated CRM pages set no tracking cookies and create no tracking rows.
- `php artisan test` (full suite) — 475 passed (1527 assertions), 0 failures. CRM, Metadata Platform, and Revenue modules unaffected.

## Did Any Architectural Assumptions Change?

- No. `MarketingTrackingService` remains the only write authority, models remain logic-free, and no provider-specific or attribution logic exists.
- One addition beyond the literal deliverables list: the `last_activity_at` column (additive, reversible migration) to make the inactivity timeout explicit rather than inferred.

## CTO Recommendation

Proceed to Phase 7B.3 (tracking parameter capture and channel classification) after review.

The next sub-phase should include:

- UTM and click-ID capture on the tracking payload, stored per the contract's parameter table.
- The `ChannelClassifier` with contract precedence (click ID → UTM → referrer rules → direct) populating the currently-null touch columns.
- The contract rule that a mid-session source change starts a new session, layered onto `resolveSession`.
- No attribution writes until 7B.4.
