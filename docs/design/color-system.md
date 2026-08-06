# Deliverable 2 — Color System

Semantic and domain color system for Konnect Nex.

---

## Principles

1. **Semantic first** — use `success` / `danger`, not raw green/red in product logic.  
2. **Neutrals carry the UI** — color accents actions and status, not every surface.  
3. **AA contrast** — text on surfaces ≥ 4.5:1; UI chrome ≥ 3:1.  
4. **Color ≠ sole signal** — pair with icon/text ([accessibility.md](./accessibility.md)).  
5. **One primary** — indigo-family brand aligned with current app; org accent may override ([theme-architecture.md](./theme-architecture.md)).  
6. **In-app restraint** — no purple glow walls; landing pages may be richer.

---

## Brand & action

### Primary (default = Indigo)

| Step | Hex (default) | Use |
|------|---------------|-----|
| 50 | `#eef2ff` | Tints, selected row soft |
| 100 | `#e0e7ff` | Soft backgrounds |
| 200 | `#c7d2fe` | Borders hover |
| 500 | `#6366f1` | Focus ring, links |
| 600 | `#4f46e5` | Primary button (matches existing `--bs-primary`) |
| 700 | `#4338ca` | Primary hover/active |
| 900 | `#312e81` | Rare text on light |

### Secondary

Neutral-forward: `neutral-100` fill, `neutral-700` text, `neutral-300` border — secondary buttons.

### Accent

Optional highlight (`sky-600` / org brand). Use sparingly for badges, charts highlight series — **not** a second primary button color.

---

## Feedback

| Role | Base | Soft bg | Use |
|------|------|---------|-----|
| **Success** | `#059669` (emerald-600) | emerald-50 | Saved, paid, approved |
| **Warning** | `#d97706` (amber-600) | amber-50 | Due soon, watch |
| **Danger** | `#dc2626` (red-600) | red-50 | Errors, destructive, overdue |
| **Info** | `#0284c7` (sky-600) | sky-50 | Neutral alerts, tips |

---

## Neutrals (Slate-aligned)

| Step | Role |
|------|------|
| 0 / white | `#ffffff` card surface |
| 50 | Page background soft |
| 100 | Muted surface, zebra |
| 200 | Default border |
| 300 | Strong border |
| 400 | Placeholder, muted icon |
| 500 | Secondary text |
| 600 | Body secondary |
| 700 | Body text |
| 800 | Headings |
| 900 | Sidebar / inverse |
| 950 | Deep inverse |

Default **body text:** neutral-700/800 on white.  
**Muted:** neutral-500.

---

## Surfaces & background

| Token | Light | Use |
|-------|-------|-----|
| `bg.app` | neutral-50 / slate-50 | Main canvas |
| `bg.elevated` | white | Cards, modals |
| `bg.sunken` | neutral-100 | Wells, code |
| `bg.overlay` | neutral-900 @ 40–50% | Backdrop |
| `surface.card` | white + border subtle | Default card |
| `surface.muted` | neutral-50 | Nested panels |

---

## Sidebar

| Token | Value | Use |
|-------|-------|-----|
| `sidebar.bg` | slate-900 (`#0f172a`) | Current shell |
| `sidebar.border` | slate-800 | Dividers |
| `sidebar.text` | white | Primary labels |
| `sidebar.textMuted` | slate-400 | Meta |
| `sidebar.active` | indigo-600 / white | Active item (current avatar chip uses indigo-600) |
| `sidebar.hover` | slate-800 | Hover |

Light sidebar variant reserved for white-label; default remains dark enterprise shell.

---

## Cards

| State | Border | Shadow | Bg |
|-------|--------|--------|-----|
| Rest | neutral-200 | shadow-sm | white |
| Hover | neutral-300 | shadow-sm | white |
| Selected | primary-200 | shadow-sm | primary-50 |
| Danger zone | red-200 | — | red-50 |

Avoid hover glow (`shadow-indigo-*`) inside app chrome.

---

## Charts

Ordered series (colorblind-safer defaults):

1. primary-600  
2. sky-600  
3. emerald-600  
4. amber-600  
5. violet-600 (series only)  
6. rose-600  
7. neutral-500  
8. cyan-700  

Rules: max 6 series before “Other”; legends always; no relying on red/green alone for two-state charts.

---

## Status colors (entities)

| Status family | Color role | Example labels |
|---------------|------------|----------------|
| Neutral / draft | neutral | Draft, Open |
| Active / in progress | info / primary | Active, In progress |
| Success / done | success | Won, Paid, Completed, Approved |
| Warning | warning | On hold, Pending |
| Danger | danger | Lost, Overdue, Rejected, Failed |
| Archived | neutral muted | Archived |

Badges: soft bg + solid text/icon.

---

## Priority colors

| Priority | Color |
|----------|-------|
| Lowest | neutral-500 |
| Low | sky-600 |
| Medium | amber-600 |
| High | orange-600 |
| Urgent / Critical | red-600 |

Always include text label.

---

## Project health colors

| Health | Color | Label |
|--------|-------|-------|
| Healthy | emerald-600 | Healthy |
| Watch | amber-600 | Watch |
| At risk | orange-600 | At risk |
| Critical | red-600 | Critical |

Matches [widget-standards.md](../product/widget-standards.md) health indicators.

---

## Leave colors

| Leave type accent | Suggestion |
|-------------------|------------|
| Annual | primary-600 |
| Sick | rose-600 |
| Casual | sky-600 |
| Unpaid | neutral-600 |
| Compensatory | emerald-600 |
| Other | violet-600 (badge only) |

Configurable per org leave type later; defaults above.

---

## Marketing colors

| Use | Token |
|-----|-------|
| Attribution positive | emerald |
| Provider connected | success |
| Provider error | danger |
| Campaign accent | accent / sky |

Keep Marketing workspace accent distinct from CRM primary only via chips/headers — not a full re-skin.

---

## Dark mode palette

| Role | Dark default |
|------|--------------|
| `bg.app` | slate-950 |
| `bg.elevated` | slate-900 |
| `border` | slate-700 |
| `text` | slate-100 |
| `textMuted` | slate-400 |
| `primary` | indigo-400 / 500 (buttons may stay 500–600 with white text if contrast OK) |
| Sidebar | May match app or stay slate-950 |

Full rules: [theme-architecture.md](./theme-architecture.md). Dark is **opt-in**; do not force as default.

---

## Forbidden patterns (in-app)

- Purple-to-indigo full-page gradients as app background  
- Neon glow shadows on cards  
- Low-contrast gray-on-gray body text  
- Status conveyed only by color dots without labels  

---

## CSS variable sketch (Phase 14)

```css
:root {
  --nova-color-primary-600: #4f46e5;
  --nova-color-bg-app: #f8fafc;
  --nova-color-surface-card: #ffffff;
  --nova-color-text: #334155;
  --nova-color-sidebar-bg: #0f172a;
}
```
