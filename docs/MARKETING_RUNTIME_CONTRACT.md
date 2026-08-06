# Marketing Runtime Contract

## Status

- Phase: P7B.F (Foundation Freeze)
- State: **Frozen**
- Companion documents: `docs/MARKETING_PLATFORM_OVERVIEW.md`, `docs/P7_MARKETING_PLATFORM_TDS.md`
- Implementation reference: Phase 7B.1 (Foundation), Phase 7B.2 (Tracking Runtime)

## Purpose

This contract freezes the anonymous visitor tracking runtime. Every future consumer (provider adapters, reporting, automation, AI) must treat visitors, sessions, and touches as immutable observations owned by the Marketing Platform.

`MarketingTrackingService` is the **single write authority** for all tracking persistence. Controllers, middleware, and other services may orchestrate; they must never insert or update tracking rows directly.

## Single Write Authority

| Concern | Authority |
| --- | --- |
| Visitor create / lookup / last-seen | `MarketingTrackingService` |
| Session create / resolve / close / activity | `MarketingTrackingService` |
| Touch create (including classified page views) | `MarketingTrackingService` |
| Cookie issuance | `MarketingTrackingMiddleware` (orchestration only; persistence via service) |

Rules:

- No other service, controller, job, or adapter may write to `marketing_visitors`, `marketing_sessions`, or `marketing_touches`.
- Classification results from `MarketingChannelClassificationService` are persisted only through `MarketingTrackingService::recordPageView` / `createTouch`.
- Backfill never rewrites tracking history.

## Visitor Lifecycle

```
First tracked request (no valid visitor cookie)
        → createVisitor (platform-issued UUID)
        → set visitor cookie
        → last_seen_at = now

Returning request (valid visitor cookie)
        → findVisitor by UUID
        → updateLastSeen (meaningful-only; see Activity Granularity)
        → re-issue visitor cookie (sliding expiry)

Unknown / purged visitor UUID
        → create fresh visitor
        → issue new cookie
        → request never fails because of stale cookies
```

Rules:

- A Visitor is an anonymous browser identity identified by a platform-generated UUID.
- Visitors are tenant-unowned at capture (`organization_id` is null until attribution).
- Visitor identity persists across sessions. Session rollover never changes the visitor UUID.
- IP address and user agent are diagnostic context only. They are never identity keys.
- No fingerprinting of any kind.

## Session Lifecycle

```
Resolve session for visitor
        ├─ valid session cookie → open, same visitor, not expired
        │       → continue session
        │       → recordActivity (meaningful-only)
        │       → re-issue session cookie
        │
        ├─ session cookie missing
        │       → reuse activeSessionFor(visitor) if open and not expired
        │       → else createSession
        │
        └─ expired (inactivity > session_timeout_minutes)
                → closeSession (ended_at = last known activity)
                → createSession (new UUID; visitor unchanged)
                → issue new session cookie
```

Rules:

- Default inactivity timeout: **30 minutes** (`config('marketing.tracking.session_timeout_minutes')`).
- At most one open session per visitor. `createSession` closes any still-open session transactionally.
- `closeSession` is idempotent.
- Session rows summarize visit window (`started_at`, `last_activity_at`, `ended_at`).

## Touch Creation

A touch is one observed interaction (currently: `page_view`).

Rules:

- Touches are **immutable** once recorded. Corrections create new records.
- Page views are recorded via `recordPageView`, which:
  1. Classifies the URL + referrer (pure, no writes).
  2. Persists a `MarketingTouch` with landing page, referrer, UTM fields, click IDs, and channel classification.
- Landing pages are stored stripped of tracking parameters (`utm_*` and click IDs). Non-tracking query parameters survive.
- URL fields are capped at 2048 characters; user agents at 1024.
- Absent values are stored as `null`, never as empty strings.
- Client-supplied `occurred_at` values in the future are clamped to server time.

## Cookie Strategy

| Cookie | Default name | Lifetime | Purpose |
| --- | --- | --- | --- |
| Visitor | `nova_mk_visitor` | 2 years (sliding) | Long-term anonymous identity |
| Session | `nova_mk_session` | Session timeout (sliding) | Current visit window |

Rules:

- Cookies carry opaque platform-issued UUIDs only.
- Cookies are `httpOnly`, `SameSite=Lax`, `secure` per `session.secure`, and encrypted by Laravel's `EncryptCookies` middleware.
- Both cookies are re-issued on every successful tracked response so expiry slides with activity.
- Cookie names and lifetimes are config-driven (`config/marketing.php`); never hardcode in consumers.

## Tracking Endpoint

| Item | Contract |
| --- | --- |
| Route | `POST /marketing/track` |
| Auth | None (public beacon) |
| CSRF | Excluded (beacon-style; compensating controls below) |
| Throttle | `marketing-tracking` limiter, per-IP, default 120/minute |
| Success | `204 No Content` + re-issued cookies |
| Validation | `TrackPageViewRequest` — closed event whitelist (`page_view`), URL format/length, date validation |

Compensating security controls: per-IP throttling, strict payload validation, `SameSite=Lax` httpOnly cookies, no authenticated side effects, empty success body.

## Activity Granularity

Visitor `last_seen_at` and session `last_activity_at` update only when older than `activity_granularity_seconds` (default **60 seconds**). Rapid page views within the window cause zero timestamp writes.

## Runtime Guarantees

1. **Idempotent identity recovery** — stale cookies never break the request; a fresh identity is issued.
2. **Visitor continuity** — session rollover preserves the visitor UUID.
3. **One active session** — enforced transactionally at session create.
4. **Meaningful-only writes** — activity timestamps are rate-limited by granularity.
5. **No tenant assignment at capture** — `organization_id` remains null until attribution claims the visitor.
6. **CRM isolation** — authenticated CRM pages do not set tracking cookies or create tracking rows unless the tracking middleware is explicitly attached.
7. **Degradation without blocking** — tracking failures must never block CRM lead creation or conversion.

## Tenant Ownership Model

| Table | `organization_id` | Notes |
| --- | --- | --- |
| `marketing_visitors` | Nullable | Claimed at attribution time |
| `marketing_sessions` | None | Ownership transitive via visitor |
| `marketing_touches` | None | Ownership transitive via session → visitor |

Tracking models do **not** use `BelongsToOrganization`. Its global scope would hide unowned visitors; its creating hook would force-assign tenant context incorrectly for anonymous capture and queue workers.

## Extension Rules

Allowed without contract revision:

- Config changes to cookie names, lifetimes, timeout, rate limit, activity granularity.
- New touch event types added to the request whitelist **and** documented by contract revision.
- Additional diagnostic fields on touches via additive migrations.

Prohibited:

- Writing tracking rows outside `MarketingTrackingService`.
- Using IP / user agent / fingerprint as identity.
- Assigning `organization_id` on visitors before attribution.
- Rewriting existing touches or sessions (except closing a session / updating activity timestamps as defined above).

## Non-Responsibilities

- Consent management / cookie banners (tenant responsibility).
- Cross-device identity stitching.
- Mid-session source-change session rollover (deferred; classification still captures new parameters on subsequent touches).
- Provider click-ID resolution into campaigns (future adapter phase).
