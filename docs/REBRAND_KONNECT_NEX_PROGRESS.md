# Progress — Enterprise Rebranding: NovaCRM → Konnect Nex

**Status:** Complete  
**Date:** 2026-08-01  
**Priority:** High  
**Scope:** Branding-only product rename. No business logic, database schema, or public API contract changes.

---

## Outcome

User-facing product name is **Konnect Nex** across application UI, mail defaults, docs, API documentation titles, Postman collections, and presentation assets. Branding is centralized in `config/branding.php` so future renames are configuration-driven.

Composer namespaces, PHP class names, migration filenames, database tables, local path `nova-crm`, test DB `novacrm_testing`, and webhook header names `X-NovaCRM-*` remain unchanged for backward compatibility.

---

## Deliverables

| # | Area | Status | Notes |
|---|------|--------|-------|
| 1 | Branding configuration | Done | `config/branding.php` — product name, short name, company, copyright, support email, filename prefix |
| 2 | Application / platform config | Done | `config/app.php` default name; `config/platform.php` `configuration_defaults.branding.product_name` |
| 3 | Environment example | Done | `.env.example` → `APP_NAME="Konnect Nex"` + optional `BRAND_*` keys; live `.env` not modified |
| 4 | Layouts & shell | Done | `app`, `guest`, `platform`, `platform-guest`, `careers`; titles, footer, sidebar labels |
| 5 | Auth screens | Done | Inherit guest / platform-guest branding (no hardcoded product name) |
| 6 | Welcome / marketing landing | Done | Meta, title, nav, preview chrome, footer use `config('branding.*')` |
| 7 | Dashboard / platform copy | Done | Platform dashboard subtitle; org setup; imports; integrations |
| 8 | Emails | Done | Identity invitation; platform welcome template default subject |
| 9 | Notifications / in-app copy | Done | Project notification preferences; careers footer |
| 10 | Browser metadata | Done | `application-name`, `apple-mobile-web-app-title` on primary layouts + welcome |
| 11 | PDFs / Excel / CSV exports | N/A | No hardcoded NovaCRM filenames; org name used in PDF brand block |
| 12 | API docs / Postman / OpenAPI | Done | Display titles/descriptions updated; endpoint paths unchanged |
| 13 | Documentation | Done | README, UPGRADE, `docs/**`, sales/SOP/mobile guides (~160 content files) |
| 14 | Presentation assets | Done | `presentation/build_presentation.py` copy + `KonnectNex_Presentation.*` output names |
| 15 | Seeders | Done | Demo product license / opportunity titles; demo emails `@novacrm.test` kept |
| 16 | Tests | Done | `ExampleTest`, workflow string fixtures; `phpunit.xml` sets `APP_NAME=Konnect Nex` |
| 17 | Logos / favicon | Deferred | No replacement image assets in repo; keep existing filenames when assets arrive |
| 18 | Installer | N/A | No installer UI present |
| 19 | Exception pages | N/A | No custom branded `errors/*` views; framework defaults |
| 20 | Webhook API headers | Preserved | `X-NovaCRM-Event`, `X-NovaCRM-Delivery`, `X-NovaCRM-Signature` unchanged |

---

## Architecture

```
APP_NAME / BRAND_*  →  config/branding.php
                           ↓
              config('branding.product_name')
                           ↓
        Blade layouts · mail · platform defaults · UI copy

Public API (unchanged)
  routes /api/*          — same URLs
  X-NovaCRM-* headers    — same names (consumers)
  DB / namespaces / classes — unchanged
```

---

## Key paths

### Configuration

- `config/branding.php` — canonical product display strings
- `config/app.php` — `name` default `Konnect Nex`
- `config/platform.php` — `configuration_defaults.branding.product_name`
- `.env.example` — `APP_NAME`, optional `BRAND_SHORT_NAME`, `BRAND_COMPANY_NAME`, `BRAND_COPYRIGHT`, `BRAND_SUPPORT_EMAIL`, `BRAND_FILENAME_PREFIX`

### Application code

- `app/Services/Platform/PlatformConfigurationService.php` — welcome email subject
- `app/Services/Recruitment/Providers/JitsiMeetProvider.php` — room prefix via `filename_prefix`
- `app/Services/Recruitment/RecruitmentWebhookService.php` — **headers left as `X-NovaCRM-*`**

### Views (representative)

- `resources/views/layouts/{app,guest,platform,platform-guest,careers}.blade.php`
- `resources/views/welcome.blade.php`
- `resources/views/emails/identity/invitation.blade.php`
- `resources/views/platform/dashboard.blade.php`
- `resources/views/platform/partials/sidebar.blade.php`
- `resources/views/organizations/setup.blade.php`

### Docs & commercial

- `README.md`, `UPGRADE.md`
- `docs/**` (product, sales, SOPs, mobile, release, etc.)
- `docs/mobile/openapi.yaml`
- `postman/NovaCRM-API.postman_collection.json` (collection **name** updated; filename kept)
- `postman/NovaCRM-HRMS-Mobile.postman_collection.json`

### Seed / presentation / tests

- `database/seeders/PresentationDemoSeeder.php`
- `presentation/build_presentation.py`, `presentation/README.md`
- `tests/Feature/ExampleTest.php`
- `phpunit.xml` — `APP_NAME=Konnect Nex`

---

## Branding config reference

```php
// config/branding.php
'product_name'       => env('APP_NAME', 'Konnect Nex'),
'product_short_name' => env('BRAND_SHORT_NAME', 'Konnect'),
'company_name'       => env('BRAND_COMPANY_NAME', 'Konnect Nex'),
'copyright'          => env('BRAND_COPYRIGHT', '© Konnect Nex'),
'support_email'      => env('BRAND_SUPPORT_EMAIL', 'support@example.com'),
'filename_prefix'    => env('BRAND_FILENAME_PREFIX', 'KonnectNex'),
```

Prefer `config('branding.product_name')` (or `config('app.name')` when they share `APP_NAME`) over hardcoded product strings in new UI work.

---

## Explicit non-goals (preserved)

| Item | Reason |
|------|--------|
| PHP namespaces / class names | Not a branding surface; would break the codebase |
| Composer package name | Unchanged (`laravel/laravel` skeleton) |
| DB tables / migrations | Tenant data & schema stability |
| `novacrm_testing` | Isolated test DB identifier |
| Path / deploy examples `nova-crm` | Host filesystem / URL path |
| `X-NovaCRM-*` webhook headers | Public API compatibility |
| CSS `--nova-*` design tokens | Technical token prefix, not display copy |
| Live / production `.env` | Operator must set `APP_NAME` per environment |

---

## Operator checklist

- [ ] Set `APP_NAME="Konnect Nex"` in each environment `.env`
- [ ] Optional: set `BRAND_*` overrides if short name / support email differ
- [ ] `php artisan config:clear` (or rebuild `config:cache`) after env change
- [ ] Spot-check login, dashboard footer, platform login, welcome page, invitation email
- [ ] Replace favicon / logo binaries when design assets are ready (keep filenames if possible)
- [ ] Regenerated presentation: `KonnectNex_Presentation.pptx` / `.pdf` via presentation builder

---

## Verification

| Check | Result |
|-------|--------|
| `tests/Feature/ExampleTest` | Pass (asserts `config('branding.product_name')` + current hero copy) |
| `WorkflowConditionEvaluatorTest` | Pass (fixtures use generic `Acme CRM` strings) |
| Hardcoded `NovaCRM` in `resources/views` / `app` (ex-webhooks) | Cleared |
| Remaining `NovaCRM` in app code | Webhook headers only |
| Schema / routes / API URLs | Unchanged |

---

## Acceptance criteria

| Criterion | Met |
|-----------|-----|
| User-facing branding is Konnect Nex | Yes |
| Centralized branding config introduced | Yes |
| Business logic unchanged | Yes |
| Database schema unchanged | Yes |
| Public APIs backward compatible | Yes (headers + URLs preserved) |
| Tenant data unaffected | Yes |
| Namespaces / migrations / vendor not renamed | Yes |

---

## Follow-ups (optional)

1. Supply and drop in logo / favicon / touch-icon assets under existing public paths.
2. Optionally alias or document `X-NovaCRM-*` → future `X-KonnectNex-*` with a deprecation window (not done — would be an API change).
3. Rename Postman collection **filenames** only if tooling consumers are updated in lockstep.

---

## QA remediation (2026-08-03)

Manual QA still showed **Nova CRM** on Home/Login because local `.env` retained `APP_NAME="Nova CRM"`. See [REBRAND_QA_REMEDIATION_PROGRESS.md](./REBRAND_QA_REMEDIATION_PROGRESS.md) for the audit, env fix, cache clear, and HTTP verification.
