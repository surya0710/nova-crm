# P7 Marketing Attribution Platform - Phase 7B.3 Impact Report

## Phase

Phase 7B.3 - Channel Classification

## What Changed?

- Added `MarketingChannelClassificationService`: a pure, deterministic, provider-agnostic classification layer. It performs zero writes (verified by test); it inspects UTM parameters, click identifiers, and the referrer, and returns a classification result.
- Extended `MarketingTrackingService::recordPageView` to delegate classification and persist the enriched touch. It remains the single write authority; the middleware and controller are unchanged.
- Added one additive, reversible migration on `marketing_touches`: nullable `gclid`, `fbclid`, `msclkid` (each indexed per the TDS click-ID enrichment plan) and nullable `referrer_host`.
- Added a `classification` section to `config/marketing.php`: click-ID rules, search engine domains, social network domains, and medium groups (paid, social, email, display). All classification data is config-driven; extending recognition requires no code changes.
- Landing pages are now stored stripped of tracking parameters (`utm_*` and click IDs), per the contract's landing-page rule. Non-tracking query parameters survive.
- Revised the Marketing Attribution Contract channel table with one addition: `display`, using the contract's built-in extension rule. No other contract semantics changed.
- Added `tests/Feature/MarketingTrackingClassificationTest.php` (27 tests, 93 assertions) and updated one 7B.2 test whose "channel fields are null" assertion was explicitly a pre-classification placeholder.

## Architecture

```
MarketingTrackingController (unchanged)
    → TrackPageViewRequest (unchanged)
    → MarketingTrackingService::recordPageView
        → MarketingChannelClassificationService::classify(url, referrer)   [pure, no writes]
        ← classification result (channel, source, medium, campaign, term,
           content, gclid, fbclid, msclkid, referrer_host)
        → MarketingTrackingService::createTouch(...)                        [single write authority]
```

- All classification rules live in the classification service and its config. Nothing is embedded in the controller or middleware.
- The 7B.2 runtime pipeline (visitor identity, cookies, session lifecycle) is untouched.

## Classification Flow

Precedence per the Marketing Attribution Contract — click ID → UTM → referrer → direct:

1. **Click identifiers** (strongest signal): `gclid`/`msclkid` → Paid Search; `fbclid` → Paid Social. Click IDs are captured verbatim, with no provider validation and no API calls. When UTM values accompany a click ID, the UTM source/medium win for those fields (they reflect the advertiser's own naming); the channel comes from the click ID.
2. **UTM parameters**: medium checked against email → display → paid → social → organic/search → referral groups. Paid mediums split into Paid Social vs Paid Search by whether the source (or referrer) is a known social network or the medium mentions "social".
3. **Referrer rules**: known search engine → Organic Search (`medium=organic`); known social network → Organic Social (`medium=social`); any other external host → Referral with the host as source. Self-referrals (referrer host equals page host) are internal navigation and are ignored.
4. **Direct**: no UTM, no click IDs, no external referrer.

Normalization rules (contract-compliant):

- `utm_source`/`utm_medium` lowercased and trimmed; `utm_campaign`/`utm_term`/`utm_content` trimmed, casing preserved.
- Empty or whitespace-only values become null — empty strings are never stored.
- Referrer hosts are lowercased with any leading `www.` removed.
- All captured values are length-capped (255 for parameters, 2048 for URLs).
- Unparseable URLs and missing referrers degrade gracefully to null / Direct.

## Supported Channels

`direct`, `organic_search`, `paid_search`, `organic_social`, `paid_social`, `referral`, `email`, `display`, `other`

## Supported Search Engines

Google, Bing, Yahoo, DuckDuckGo, Baidu, Yandex, Ecosia — matched by host label or explicit domain, so regional variants (`google.co.uk`, `search.yahoo.com`) classify correctly.

## Supported Social Networks

Facebook (incl. `fb`, Messenger), Instagram, LinkedIn (incl. `lnkd`), X (incl. `twitter`, `t.co`, `x.com`), Threads, Reddit (incl. `redd.it`), YouTube (incl. `youtu.be`), TikTok, Pinterest, WhatsApp (incl. `wa.me`)

## Supported Click Identifiers

- `gclid` (Google Ads) → Paid Search
- `msclkid` (Microsoft Ads) → Paid Search
- `fbclid` (Meta) → Paid Social

Capture-only: stored verbatim for future attribution/enrichment phases. The mapping is configuration data, not provider integration — no OAuth, no API calls, no validation against providers.

## Testing Summary

- `php artisan test --filter=MarketingTracking` — 55 passed (211 assertions): 15 from 7B.1, 13 from 7B.2 (one updated as noted), 27 new:
  - UTM: all five parameters captured; normalization (trim, casing); empty-value-to-null.
  - Click IDs: gclid/msclkid/fbclid capture and channel mapping; precedence over referrer; UTM interplay.
  - Referrer: host normalization; search/social/referral/direct detection; self-referral exclusion; graceful missing referrer.
  - Channels: every supported channel has at least one deterministic test.
  - Pipeline: classification persisted on touches; landing-page stripping; end-to-end endpoint test; classifier-performs-no-writes guarantee.
- `php artisan test` (full suite) — 502 passed (1620 assertions), 0 failures. Baseline of 475 fully green plus the 27 new tests. No CRM, Metadata, or Revenue changes.

## Performance Considerations

- Classification is pure in-memory computation on the existing write path: no extra queries, no HTTP calls, no caching needed.
- Domain matching iterates a small config map (~17 entries) per unclassified referrer — negligible against the touch insert itself.
- The three click-ID indexes add minor write overhead to a high-volume table; they are required by the TDS for future click-ID → campaign resolution (7D/7E) and are cheaper to add now than to backfill later.

## Future Extensibility

- New search engines, social networks, or medium groups: config entries only.
- New click identifiers (e.g. `ttclid`, `li_fat_id`): one config entry maps the parameter to a channel/source/medium; extraction, stripping, and persistence pick it up automatically once a column exists.
- Future providers (7D+) resolve stored click IDs into campaign/ad references without touching classification.
- Deferred, as scoped: the contract's "mid-session source change starts a new session" rule remains open for a later phase, alongside attribution (7B.4).

## Did Any Architectural Assumptions Change?

- No. The pipeline, write authority, and layering are unchanged. The only contract edit is the `display` channel row, added through the contract's own extension mechanism because this phase's classification set includes display advertising mediums.

## CTO Recommendation

Proceed to Phase 7B.4 (Lead Attribution) after review. That phase should consume this classification output to freeze first/last-touch attribution at lead submission, resolve visitor ownership (`organization_id`), and emit `lead_created` conversion events per the TDS.
