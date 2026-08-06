# Deliverable 1 — Design Tokens

Platform-wide design tokens for Konnect Nex. Names are semantic; values are defaults for **light theme**. Dark and brand overrides: [theme-architecture.md](./theme-architecture.md), [color-system.md](./color-system.md).

---

## Naming convention

```
--nova-{category}-{name}[-{variant}]
```

Examples: `--nova-color-primary-600`, `--nova-space-4`, `--nova-radius-md`, `--nova-z-modal`.

Tailwind mapping (Phase 14): prefer CSS variables inside `theme.extend`.

---

## Colors (token categories)

| Token group | Purpose |
|-------------|---------|
| `color.primary.*` | Brand actions, links, focus |
| `color.secondary.*` | Secondary actions |
| `color.accent.*` | Highlights (sparingly) |
| `color.success|warning|danger|info.*` | Feedback |
| `color.neutral.*` | Text, borders, icons |
| `color.surface.*` | Cards, panels |
| `color.bg.*` | Page / app backgrounds |
| `color.sidebar.*` | App sidebar |
| `color.chart.*` | Series palette |
| `color.status.*` | Entity status |
| `color.priority.*` | Task/issue priority |
| `color.health.*` | Project/portfolio health |
| `color.leave.*` | Leave type accents |
| `color.marketing.*` | Marketing workspace accents |

Full scales: [color-system.md](./color-system.md).

---

## Typography tokens

| Token | Default | Notes |
|-------|---------|-------|
| `font.sans` | Figtree, ui-sans, system | App UI |
| `font.mono` | ui-monospace, SFMono, Menlo | Codes, IDs |
| `font.size.xs`–`3xl` | See typography | Rem-based |
| `font.weight.normal|medium|semibold|bold` | 400–700 | |
| `font.line.tight|snug|normal|relaxed` | 1.25–1.625 | |
| `font.tracking.tight|normal|wide` | Headings / labels | |

---

## Spacing scale

4px base (Tailwind-compatible).

| Token | Rem | Px |
|-------|-----|-----|
| `space-0` | 0 | 0 |
| `space-0.5` | 0.125 | 2 |
| `space-1` | 0.25 | 4 |
| `space-1.5` | 0.375 | 6 |
| `space-2` | 0.5 | 8 |
| `space-3` | 0.75 | 12 |
| `space-4` | 1 | 16 |
| `space-5` | 1.25 | 20 |
| `space-6` | 1.5 | 24 |
| `space-8` | 2 | 32 |
| `space-10` | 2.5 | 40 |
| `space-12` | 3 | 48 |
| `space-16` | 4 | 64 |

**Page padding:** `space-4` (mobile) → `space-6`/`space-8` (desktop).  
**Section gap:** `space-6`–`space-8`.  
**Stack gap (forms):** `space-4`.

---

## Border radius

| Token | Value | Use |
|-------|-------|-----|
| `radius-none` | 0 | Dense tables optional |
| `radius-sm` | 0.25rem (4) | Inputs compact, badges |
| `radius-md` | 0.375rem (6) | Buttons, inputs default |
| `radius-lg` | 0.5rem (8) | Cards, menus |
| `radius-xl` | 0.75rem (12) | Panels, widgets |
| `radius-2xl` | 1rem (16) | Marketing only / rare |
| `radius-full` | 9999px | Avatars, pills (status only) |

In-app default card: **`radius-lg` or `xl`** — avoid oversized “soft UI” everywhere.

---

## Elevation & shadows

| Token | Use |
|-------|-----|
| `shadow-none` | Flat tables, embedded |
| `shadow-xs` | Subtle hairline lift |
| `shadow-sm` | Cards at rest |
| `shadow-md` | Dropdowns, popovers |
| `shadow-lg` | Modals |
| `shadow-focus` | Focus ring companion (color, not blur spam) |

Prefer **border + light shadow** over multi-layer glow. No indigo glow on every hover.

---

## Opacity

| Token | Value | Use |
|-------|-------|-----|
| `opacity-disabled` | 0.5 | Disabled controls |
| `opacity-muted` | 0.7 | Secondary icons |
| `opacity-overlay` | 0.4–0.5 | Modal backdrop |
| `opacity-skeleton` | 0.4 | Pulse base |

---

## Icon sizes

| Token | Px | Use |
|-------|-----|-----|
| `icon-xs` | 12 | Dense table |
| `icon-sm` | 16 | Inline, inputs |
| `icon-md` | 20 | Nav, buttons |
| `icon-lg` | 24 | Empty/feature |
| `icon-xl` | 32 | Rare hero |

Stroke: **1.5** default (match current sidebar SVGs).

---

## Avatar sizes

| Token | Px | Use |
|-------|-----|-----|
| `avatar-xs` | 24 | Tables |
| `avatar-sm` | 32 | Comments |
| `avatar-md` | 36–40 | Sidebar user |
| `avatar-lg` | 48 | Profile header |
| `avatar-xl` | 64 | Profile page |

---

## Border styles

| Token | Spec |
|-------|------|
| `border-width-DEFAULT` | 1px |
| `border-width-strong` | 2px (active tab, focus) |
| `border-color-subtle` | neutral-200 |
| `border-color-DEFAULT` | neutral-200/300 |
| `border-color-strong` | neutral-400 |
| `border-color-focus` | primary-500 |
| `divider` | 1px neutral-200 |

---

## Animation durations

| Token | Ms | Use |
|-------|-----|-----|
| `duration-instant` | 0 | Reduced motion / critical |
| `duration-fast` | 100–150 | Hover, focus |
| `duration-normal` | 200 | Panels, tabs |
| `duration-moderate` | 300 | Drawers, modals |
| `duration-slow` | 400–500 | Rare page transitions |

Easings: `ease-out` enter, `ease-in` exit, `ease-in-out` move. See [motion-system.md](./motion-system.md).

---

## Z-index hierarchy

| Token | Value | Layer |
|-------|-------|-------|
| `z-base` | 0 | Content |
| `z-sticky` | 10 | Sticky table header / page header |
| `z-sidebar` | 30 | Desktop sidebar |
| `z-dropdown` | 40 | Menus, popovers |
| `z-drawer` | 50 | Mobile nav drawer |
| `z-modal` | 60 | Dialogs |
| `z-toast` | 70 | Toasts |
| `z-command` | 80 | Command palette |
| `z-tooltip` | 90 | Tooltips |

Never invent ad-hoc `z-9999` in features.

---

## Responsive breakpoints

| Token | Min width | Alias |
|-------|-----------|-------|
| `bp-sm` | 640px | Mobile landscape |
| `bp-md` | 768px | Tablet |
| `bp-lg` | 1024px | Laptop |
| `bp-xl` | 1280px | Desktop |
| `bp-2xl` | 1536px | Large |

App shell “desktop sidebar” from **`lg` (1024)** upward.

---

## Density tokens

| Mode | Row / control scale |
|------|---------------------|
| `density-comfortable` | Default spacing |
| `density-compact` | −1 step on vertical padding (tables/forms) |

---

## Reference mapping (today)

| Current practice | Token |
|------------------|-------|
| Figtree | `font.sans` |
| `slate-*` | `color.neutral` / surfaces |
| `indigo-*` | `color.primary` |
| Sidebar `w-64` / `bg-slate-900` | `sidebar.width` / `color.sidebar` |
| Widget width 3–12 | Layout grid cols |

Phase 14 replaces scattered utilities with token-backed classes where practical.
