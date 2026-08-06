# Marketing Channel Classification Contract

## Status

- Phase: P7B.F (Foundation Freeze)
- State: **Frozen**
- Companion documents: `docs/MARKETING_ATTRIBUTION_CONTRACT.md`, `docs/MARKETING_RUNTIME_CONTRACT.md`
- Implementation reference: Phase 7B.3 (Channel Classification)

## Purpose

This contract freezes how anonymous traffic is classified into canonical channels. Classification is **deterministic**, **provider-agnostic**, and **pure** — it performs no database writes and no provider API calls.

`MarketingChannelClassificationService` is the sole classification authority. `MarketingTrackingService` is the sole persistence authority for classification results on touches.

## Single Classification Authority

| Concern | Authority |
| --- | --- |
| Classify URL + referrer | `MarketingChannelClassificationService::classify` |
| Extract / normalize parameters | `MarketingChannelClassificationService` |
| Strip tracking params from landing page | `MarketingChannelClassificationService::stripTrackingParameters` |
| Persist classification onto touch | `MarketingTrackingService` only |

Rules:

- The classifier never persists. Verified by platform tests.
- Controllers and middleware must not embed classification rules.
- Provider adapters must not invent channels or override classification at capture time. They may later **enrich** structural campaign references from stored click IDs without rewriting channel/source/medium snapshots.

## Supported Channels

Closed set (extendable only by contract revision):

| Channel slug | Meaning |
| --- | --- |
| `paid_search` | Paid search advertising |
| `paid_social` | Paid social advertising |
| `organic_search` | Unpaid search engine traffic |
| `organic_social` | Unpaid social traffic |
| `display` | Display / banner / programmatic |
| `email` | Email campaigns |
| `referral` | Third-party website referral |
| `direct` | No referrer, no tracking parameters |
| `other` | Classified traffic that fits no channel above |

Additional channels defined in the Attribution Contract for non-runtime capture paths (`sms`, `whatsapp`, `offline`, `manual`, `api`) are set at attribution/intake time, not by the page-view classifier.

Channel slugs are stable identifiers. Display labels may change; slugs never do.

## Precedence Rules

Highest signal wins:

```
1. Click ID          → channel from click-ID registry
2. UTM parameters    → channel from medium/source groups
3. Referrer rules    → search / social / referral
4. Direct            → no signals
```

Detail:

1. **Click identifiers (strongest)**  
   Present `gclid` / `msclkid` → `paid_search`. Present `fbclid` → `paid_social`.  
   When UTM values accompany a click ID, UTM `source` / `medium` win for those fields (advertiser naming). Channel still comes from the click ID.

2. **UTM parameters**  
   Medium checked against config groups in order: email → display → paid → social → organic/search → referral.  
   Paid mediums split into `paid_social` vs `paid_search` by whether the source (or referrer) is a known social network or the medium mentions "social".

3. **Referrer rules**  
   - Known search engine → `organic_search` (`medium=organic`)  
   - Known social network → `organic_social` (`medium=social`)  
   - Any other external host → `referral` (host as source)  
   - Self-referrals (referrer host equals page host) are internal navigation and are ignored.

4. **Direct**  
   No UTM, no click IDs, no external referrer.

## Supported Click IDs

| Parameter | Channel | Default source | Default medium | Provider (future) |
| --- | --- | --- | --- | --- |
| `gclid` | `paid_search` | `google` | `cpc` | Google Ads |
| `msclkid` | `paid_search` | `bing` | `cpc` | Microsoft Ads |
| `fbclid` | `paid_social` | `facebook` | `paid_social` | Meta |

Rules:

- Click IDs are stored **verbatim**. No provider validation. No API calls at classification time.
- Matching is by parameter name, not value inspection.
- A touch may carry multiple click IDs; precedence and registry order decide the channel.
- Capture-only: enrichment into campaign/ad entities is a future adapter concern.

Config location: `config('marketing.classification.click_ids')`.

## Supported UTM Fields

| Parameter | Normalization |
| --- | --- |
| `utm_source` | Trimmed, lowercased |
| `utm_medium` | Trimmed, lowercased |
| `utm_campaign` | Trimmed, casing preserved |
| `utm_term` | Trimmed, casing preserved |
| `utm_content` | Trimmed, casing preserved |

Rules:

- Empty or whitespace-only values become `null`. Empty strings are never stored.
- All captured values are length-capped at 255 characters.
- Unknown `utm_*` parameters may be preserved in an extras map in future schema extensions; they must not be silently dropped when promoted.

### Medium groups (config-driven)

| Group | Config key | Example values |
| --- | --- | --- |
| Paid | `paid_mediums` | `cpc`, `ppc`, `sem`, `paid`, `paid_search`, `paid_social`, `retargeting` |
| Social | `social_mediums` | `social`, `social_media`, `organic_social` |
| Email | `email_mediums` | `email`, `newsletter` |
| Display | `display_mediums` | `display`, `banner`, `cpm`, `programmatic` |

## Search Engine Registry

Canonical sources and host-match patterns (`config('marketing.classification.search_engines')`):

| Source | Patterns |
| --- | --- |
| `google` | `google` |
| `bing` | `bing` |
| `yahoo` | `yahoo` |
| `duckduckgo` | `duckduckgo` |
| `baidu` | `baidu` |
| `yandex` | `yandex` |
| `ecosia` | `ecosia` |

Domain matching rules:

- Entries containing a dot match the host exactly or as a suffix (e.g. `t.co`).
- Entries without a dot match any host label (e.g. `google` matches `www.google.co.uk`).
- Referrer hosts are lowercased with any leading `www.` removed.

## Social Network Registry

| Source | Patterns |
| --- | --- |
| `facebook` | `facebook`, `fb`, `messenger` |
| `instagram` | `instagram` |
| `linkedin` | `linkedin`, `lnkd` |
| `x` | `twitter`, `x.com`, `t.co` |
| `threads` | `threads` |
| `reddit` | `reddit`, `redd.it` |
| `youtube` | `youtube`, `youtu.be` |
| `tiktok` | `tiktok` |
| `pinterest` | `pinterest` |
| `whatsapp` | `whatsapp`, `wa.me` |

## Classification Result Shape

`classify(?string $url, ?string $referrer)` returns:

```
channel, source, medium, campaign, term, content,
gclid, fbclid, msclkid, referrer_host
```

All fields except `channel` are nullable. Unparseable URLs and missing referrers degrade gracefully to null / `direct`.

## Extension Rules

Allowed via **config only** (no code change required for recognition lists):

- New search engine domains.
- New social network domains.
- New medium group members.

Requires **contract revision** (+ typically a config entry and persistence column):

- New canonical channel slugs.
- New click ID parameters (e.g. `ttclid`, `li_fat_id`).
- Changes to precedence order.

Prohibited:

- Provider-specific branching inside the classifier.
- Rewriting historical touch classification.
- Calling provider APIs during classification.
- Controllers or CRM services implementing their own channel mapping.

## Non-Responsibilities

- Campaign / ad / keyword resolution from click IDs (provider adapters).
- Attribution model selection (`first_touch`, etc.).
- Writing `leads.source` compatibility values.
- Consent or bot filtering beyond existing rate limits.
