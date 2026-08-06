# Marketing Attribution Contract

## Status

- Phase: P7A (Architecture) → runtime frozen in **P7B.F**
- State: **Approved / Stable**
- Companion documents: `docs/P7_MARKETING_PLATFORM_TDS.md`, `docs/MARKETING_ATTRIBUTION_RUNTIME_CONTRACT.md`, `docs/MARKETING_PLATFORM_OVERVIEW.md`
- Semantic authority for attribution meaning. Runtime write/read rules for the implemented foundation live in the P7B.F contract set.

## Purpose

This contract defines the canonical semantics of marketing attribution in Konnect Nex. It freezes the meaning of attribution data independently of any advertising provider, storage decision, or reporting feature.

Every producer (tracking scripts, landing pages, lead intake API, provider adapters, manual imports) and every consumer (reports, dashboards, exports, future analytics) must obey this contract. The Marketing Attribution Platform is the single attribution authority. Marketing providers consume the platform; they do not define it.

## Attribution Authority

Rules:

- The Lead Attribution record is the only authoritative statement of where a Lead came from.
- The legacy `leads.source` column remains as a coarse, human-facing compatibility field. It is derived from attribution when attribution exists. It is never the source of truth.
- No feature may infer attribution from `custom_fields`, notes, audit logs, or provider payloads directly. All such data flows into the platform first and is read back as attribution.
- Attribution survives the Lead lifecycle: when a Lead converts to a Customer and/or Opportunity, attribution follows the chain `Lead → Customer → Opportunity → Quotation/Invoice → Payment` without being copied or re-derived.

## Canonical Channel Taxonomy

Every attribution record resolves to exactly one canonical channel. Channels are platform-defined and closed; providers map into them.

| Channel slug | Meaning |
| --- | --- |
| `paid_social` | Paid social advertising (Meta, LinkedIn, TikTok, ...) |
| `paid_search` | Paid search advertising (Google Ads, Microsoft Ads, ...) |
| `organic_search` | Unpaid search engine traffic |
| `organic_social` | Unpaid social traffic |
| `display` | Display / banner / programmatic advertising (added in P7B.3 per the extension rule below) |
| `direct` | No referrer, no tracking parameters |
| `referral` | Third-party website referral |
| `email` | Email campaigns |
| `sms` | SMS campaigns |
| `whatsapp` | WhatsApp outreach or campaigns |
| `offline` | Events, print, phone, walk-in, tradeshows |
| `manual` | Manually entered or imported without source data |
| `api` | API intake without richer source data |
| `other` | Classified traffic that fits no channel above |

Rules:

- Channel slugs are stable identifiers. Display labels may change; slugs never do.
- Provider adapters never invent channels. A new provider maps to an existing channel or the platform team extends this table by contract revision.
- Classification precedence (highest first): explicit click ID → explicit UTM parameters → known referrer domain rules → referrer present → direct.

## Source Identity

An attribution source is described by three orthogonal fields, in addition to the channel:

- `source` — normalized origin (e.g. `google`, `meta`, `linkedin`, `newsletter`, `partner-site.com`).
- `medium` — normalized mechanism (e.g. `cpc`, `paid_social`, `email`, `referral`, `organic`).
- `provider` — the marketing provider slug when the traffic is tied to a managed provider (e.g. `meta`, `google_ads`), otherwise null.

Rules:

- `source` and `medium` are lowercase, trimmed, and length-limited (255). Values are stored as received after normalization; the platform does not rewrite user campaign naming.
- `provider` is only set when the platform can bind the touch to a registered Marketing Provider (via click ID, UTM convention, or adapter sync). Organic and referral traffic has `provider = null`.

## Touch Semantics

A touch is a single observed interaction between a visitor and the tenant's marketing surface (page view with tracking context, ad click, form view).

Rules:

- Touches are immutable once recorded. Corrections create new records; they never rewrite history.
- First touch is the earliest touch known for the visitor identity that produced the lead.
- Last touch is the most recent touch at the moment of lead submission.
- If only one touch exists, first touch and last touch are the same touch.
- If no touch exists (manual entry, offline import), the attribution record still exists with channel `manual`, `offline`, or `api`, and both touch references are null.
- First-touch and last-touch references on a Lead Attribution record are frozen at lead creation. Later visits by the same person do not modify a Lead's attribution.
- Multi-touch attribution (position-based, linear, data-driven) is a future consumer of the immutable touch history. It introduces no new write semantics.

## Visitor And Session Identity

Rules:

- A Visitor is an anonymous browser identity, identified by a platform-generated `visitor_id` (UUID) persisted in a first-party cookie/localStorage. It is tenant-scoped: the same browser produces different visitor IDs for different organizations.
- A Tracking Session groups touches within one visit. A session ends after 30 minutes of inactivity or when the traffic source changes mid-visit (a new UTM/click ID starts a new session).
- Visitor and session identifiers are opaque. They carry no personal data by themselves.
- Identity resolution (visitor → lead) happens exactly once, at lead submission, by matching the submitted `visitor_id`. The platform never merges visitors retroactively in v1.
- IP address and user agent are captured for fraud/diagnostic purposes, subject to the retention rules in the TDS. They are never used as identity keys.

## Tracking Parameter Contract

The platform recognizes the following inbound parameters:

| Parameter | Class | Notes |
| --- | --- | --- |
| `utm_source` | UTM | Normalized to lowercase |
| `utm_medium` | UTM | Normalized to lowercase |
| `utm_campaign` | UTM | Stored as received (trimmed) |
| `utm_content` | UTM | Stored as received (trimmed) |
| `utm_term` | UTM | Stored as received (trimmed) |
| `gclid` | Click ID | Google Ads |
| `fbclid` | Click ID | Meta |
| `msclkid` | Click ID | Microsoft Ads |
| `li_fat_id` | Click ID | LinkedIn |
| `ttclid` | Click ID | TikTok (future provider) |
| `landing_page` | Context | Full URL, stripped of tracking params, max 2048 chars |
| `referrer` | Context | Full referrer URL, max 2048 chars |
| `visitor_id` | Identity | Platform-issued UUID |
| `session_id` | Identity | Platform-issued UUID |
| `ip_address` | Diagnostic | Subject to retention limits |
| `user_agent` | Diagnostic | Truncated at 1024 chars |

Rules:

- All parameters are optional. Absence is recorded as null, never as empty string.
- Unknown `utm_*` parameters and unknown click-ID-shaped parameters are preserved in an extras map without interpretation. They are never silently dropped.
- Click IDs are stored verbatim. They are matched to providers by parameter name, not by value inspection.
- A single touch may carry multiple click IDs (rare, e.g. redirect chains); the classification precedence in this contract decides the channel.
- Parameters are captured server-side at intake and client-side by the tracking script. When both exist for the same submission, server-captured values win for `ip_address` and `user_agent`; client-captured values win for UTM and click IDs (they reflect the original landing).

## Lead Attribution Record

Exactly one Lead Attribution record exists per Lead once the platform is live.

The record contains, at minimum:

- Tenant ownership (`organization_id`).
- The Lead reference.
- Canonical channel, source, medium, provider slug.
- First-touch and last-touch references (nullable).
- Frozen snapshot of last-touch UTM parameters, click IDs, landing page, and referrer.
- Campaign / ad group / ad / keyword references when resolvable (nullable at capture, resolvable later by adapters).
- Capture method: `tracking`, `api`, `form`, `import`, `manual`, `webhook`.

Rules:

- The snapshot fields are immutable. Structural references (campaign, ad, keyword) may be enriched later by provider adapters resolving click IDs, and each enrichment is audit-logged.
- Leads created before the platform ships receive a backfilled attribution record with channel derived from legacy `leads.source` and capture method `import`. Backfill is additive and reversible.
- Deleting a Lead deletes its attribution record. Attribution never outlives its Lead.

## Conversion Event Contract

A Conversion Event records that something of business value happened to an attributed identity.

Canonical conversion types (closed set, extendable by contract revision):

- `lead_created`
- `lead_qualified`
- `lead_converted` (to Customer)
- `opportunity_created`
- `opportunity_won`
- `invoice_paid`
- `offline_conversion` (imported)

Rules:

- Conversion events are immutable and append-only.
- Every conversion event carries `organization_id`, conversion type, occurred-at timestamp, monetary value (nullable, minor units + currency), and references to the Lead/Customer/Opportunity/Payment that triggered it.
- Conversion events are emitted by existing CRM services (lead conversion, pipeline, payments) through the attribution platform's service API. Providers never write conversion events directly; provider offline-conversion uploads flow through the platform's import service.
- Monetary value semantics: `opportunity_won` carries the Opportunity amount (pipeline value); `invoice_paid` carries the Payment amount (collected revenue). Reports must state which value they use. Collected revenue (payments) is the default revenue definition, consistent with the existing `RevenueService`.

## Provider Identity Contract

Rules:

- A Marketing Provider is identified by a stable slug: `meta`, `google_ads`, `linkedin`, `microsoft_ads`, and future slugs.
- Provider entity hierarchies are normalized into the platform's canonical hierarchy: Account → Campaign Group → Campaign → Ad Group → Ad (→ Keyword for search providers). Provider-specific names ("Ad Set" on Meta, "Ad Group" on Google) map into the canonical Ad Group level; the original provider terminology is preserved as display metadata only.
- Every synced entity stores the provider's external ID verbatim. External IDs are unique per (organization, provider, entity type).
- The platform never exposes provider payloads raw. Consumers see only canonical entities.

## Legacy Compatibility

Rules:

- `leads.source` remains and is kept in sync: when an attribution record is created, the platform writes a mapped coarse value (e.g. channel `paid_search` + provider `google_ads` → `google_ads`) so existing lists, filters, and reports keep working unchanged.
- The existing Lead Intake API (`POST /api/v1/leads`) continues to accept `source`, `form_type`, `source_url`. These map into an attribution record with capture method `api`. New optional attribution fields are additive to the API payload.
- No existing CRM behavior, table, or endpoint changes semantics because of this platform.

## Non-Responsibilities

The following are explicitly out of scope for the attribution contract and belong to future phases or other subsystems:

- Multi-touch credit allocation models (future consumer of touch history).
- Ad spend budgeting, bid management, or campaign mutation on provider platforms (the platform reads provider data; it never manages campaigns).
- Consent management / cookie banners (the tracking script must be embeddable behind a tenant's consent tool; the platform accepts absence of client identifiers gracefully).
- Cross-device or probabilistic identity stitching.
- Real-time personalization or on-site targeting.
- Email/SMS sending (only attribution of their traffic).
