# Konnect Nex — Branding QA & Remediation Report

**Status:** Complete  
**Date:** 2026-08-03  
**Type:** QA + Bug Fix + Hardening  
**Related:** [REBRAND_KONNECT_NEX_PROGRESS.md](./REBRAND_KONNECT_NEX_PROGRESS.md)

---

## Executive summary

Manual QA showing **NovaCRM / Nova CRM** on Home, Login, and Register was **not** caused by leftover hardcoded Blade strings.

**Root cause:** Local `.env` still had `APP_NAME="Nova CRM"`. Layouts correctly call `config('branding.product_name')`, which reads `APP_NAME`, so every public page rendered the stale env value.

**Fix applied:** Updated local `.env` to `APP_NAME="Konnect Nex"`, cleared Laravel caches, verified public pages return **Konnect Nex** with zero `NovaCRM` / `Nova CRM` in HTML.

---

## Phase 1 — Audit categories

| Category | Finding | Action |
|----------|---------|--------|
| A. Hardcoded Blade / PHP UI strings | **None** in `resources/views`, `lang/`, `resources/js` | No code change |
| B. Config-driven display via stale env | **Critical** — `.env` `APP_NAME="Nova CRM"` | Updated to `Konnect Nex` |
| C. Cached config/views | Cleared via `optimize:clear` (no `config.php` cache file present) | Cleared |
| D. Platform DB branding override | `platform_settings` branding rows = **0**; resolved default = Konnect Nex | None |
| E. Webhook API headers `X-NovaCRM-*` | Present — public API compat | **Preserve** |
| F. Infra identifiers (`nova-crm` path, `novacrm_testing`, demo `@novacrm.test`) | Not user-facing product branding | **Preserve** |
| G. Historical docs / progress filenames | Document rebrand history | Acceptable |
| H. Postman/collection **filenames** | Still `NovaCRM-*.json` | Filename only; collection **title** already Konnect Nex |
| I. Package / vendor mail overrides | No `resources/views/vendor/mail` | N/A |
| J. Logos / favicon binaries | No product-text assets to swap | Deferred to design assets |

---

## Root cause detail

```
.env APP_NAME="Nova CRM"
        ↓
config('app.name') / config('branding.product_name')
        ↓
layouts/guest.blade.php  → Login / Register / Forgot / Reset / Verify / Invite
layouts/app.blade.php    → Authenticated shell title/footer
welcome.blade.php        → Home meta, nav, footer
layouts/platform*.blade.php → Platform console
```

`config('branding.company_name')` already defaulted to **Konnect Nex**, which is why copyright could show the new name while the logo title still showed **Nova CRM**.

---

## Remediation performed

| Change | Detail |
|--------|--------|
| `.env` | `APP_NAME="Konnect Nex"` |
| Caches | `php artisan optimize:clear` (config, cache, views, routes, compiled) |
| `config/branding.php` | Prefer `BRAND_PRODUCT_NAME` over `APP_NAME` when set |
| `.env.example` | Document `BRAND_PRODUCT_NAME` |

No Blade hardcoded product-name literals were found to remove.

---

## Phase 2–12 verification (HTTP)

Against `APP_URL` (`http://127.0.0.1:8000`) after env fix:

| Page | Old branding in HTML | Shows Konnect Nex | Browser title |
|------|----------------------|-------------------|---------------|
| `/` (Home) | No | Yes | `Konnect Nex — CRM, Projects, HR & Analytics` |
| `/login` | No | Yes | `Konnect Nex` |
| `/register` | No | Yes | `Konnect Nex` |
| `/forgot-password` | No | Yes | `Konnect Nex` |
| `/platform/login` | No | Yes | `Platform Login — Konnect Nex` |
| `/careers` (no org slug) | — | — | 404 expected (org-scoped careers) |

Auth views (`login`, `register`, `forgot-password`, `reset-password`, `verify-email`, `confirm-password`, `accept-invitation`) all use `<x-guest-layout>` → `layouts/guest.blade.php` branding config. No local product-name literals.

Mailables use organization name or translation keys — no `NovaCRM` subjects found under `app/Mail` / `app/Notifications`.

---

## Intentionally preserved (non-UI)

| Item | Why |
|------|-----|
| `X-NovaCRM-Event` / `Delivery` / `Signature` | Public webhook contract |
| `novacrm_testing`, `nova-crm` path examples | Infra / install path |
| `demo@novacrm.test` | Demo credentials |
| Progress/history docs mentioning NovaCRM | Historical documentation |
| CSS `--nova-*` tokens | Technical design tokens |

---

## Future-proofing rules

1. **Single source:** `config/branding.php` (optionally overridden by `BRAND_*` / `APP_NAME`).
2. **UI must use** `config('branding.product_name')` (or related keys) — no product-name literals in Blade/JS/lang.
3. **After any `.env` branding change:** run `php artisan optimize:clear` (or rebuild `config:cache`).
4. **Deploy checklist:** ensure every environment’s `.env` has `APP_NAME="Konnect Nex"` (or explicit `BRAND_PRODUCT_NAME`).

---

## Operator checklist

- [x] Local `.env` `APP_NAME` updated
- [x] Caches cleared
- [x] Home / Login / Register / Forgot Password verified via HTTP
- [x] Platform login verified
- [ ] Confirm staging/production `.env` `APP_NAME` (operator) — **do not assume**
- [ ] Replace favicon/logo binaries when design assets are ready

---

## Acceptance criteria

| Criterion | Met |
|-----------|-----|
| No user-facing page shows NovaCRM from stale env | Yes (local verified) |
| Home / Login / Register / Forgot Password show Konnect Nex | Yes |
| Branding configuration-driven | Yes |
| No hardcoded product name in Blade/lang/JS/mail | Yes |
| API headers / infra identifiers preserved | Yes |
| Caches cleared without reintroducing stale branding | Yes |

---

## Automated check

```bash
php artisan test --filter=ExampleTest
# PASS — asserts config('branding.product_name') on welcome page
```
