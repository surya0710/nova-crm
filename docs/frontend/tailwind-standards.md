# Deliverable 4 — Tailwind Standards

Tailwind conventions aligned with [../design/design-tokens.md](../design/design-tokens.md) and [../design/color-system.md](../design/color-system.md).

---

## Stack

- Tailwind 3 + `@tailwindcss/forms`  
- Content paths in `tailwind.config.js` (views + vendor pagination)  
- Font: Figtree via `theme.extend.fontFamily.sans`  

Phase 14: extend theme with CSS variable-backed colors where practical.

---

## Spacing scale

Use default Tailwind spacing (4px base). Prefer:

`0`, `0.5`, `1`, `1.5`, `2`, `3`, `4`, `5`, `6`, `8`, `10`, `12`, `16`

Page padding: `p-4 sm:p-6 lg:p-8` (matches current `app` layout).

Avoid arbitrary spacing (`p-[13px]`) unless pixel-perfect exception documented.

---

## Typography utilities

| Role | Utilities |
|------|-----------|
| Page title | `text-2xl font-semibold text-slate-900` (evolve from `text-lg` toward design scale) |
| Section | `text-xl font-semibold` |
| Body | `text-sm text-slate-700` |
| Muted | `text-sm text-slate-500` |
| Label | `text-sm font-medium text-slate-700` |
| Table header | `text-xs font-semibold uppercase tracking-wide text-slate-500` |
| KPI | `text-2xl font-semibold tabular-nums` |

Do not introduce new font families in utilities.

---

## Color usage

| Role | Prefer |
|------|--------|
| App bg | `bg-slate-50` |
| Card | `bg-white border border-slate-200` |
| Primary | `bg-indigo-600 hover:bg-indigo-700 text-white` |
| Danger | `bg-red-600` / `text-red-600` |
| Success | `text-emerald-600` / soft `bg-emerald-50` |
| Sidebar | `bg-slate-900 text-slate-300` |

Prefer semantic component variants over sprinkling `indigo-*` at callsites once `ui.button` exists.

**Forbidden in-app:** gradient backgrounds on every card, `shadow-indigo-500/50` glow spam, violet as default body accent (landing may differ).

---

## Responsive utilities

Mobile-first:

```text
class="block lg:flex"
class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3"
class="hidden lg:block"
```

Sidebar persistent from `lg:` per design system.

---

## Layout helpers

| Pattern | Utilities |
|---------|-----------|
| Card | `rounded-xl border border-slate-200 bg-white shadow-sm` |
| Stack | `flex flex-col gap-4` |
| Cluster | `flex flex-wrap items-center gap-2` |
| Page grid | `grid grid-cols-12 gap-4` + `col-span-*` |
| Truncate | `truncate` / `min-w-0` |

---

## Utility ordering (recommended)

1. Layout (`flex`, `grid`, `hidden`)  
2. Box (`w`, `h`, `p`, `m`, `gap`)  
3. Typography  
4. Color / border / shadow  
5. Interaction (`hover:`, `focus:`)  
6. Responsive variants grouped with base when possible  

Consistency > perfection; Prettier plugins optional later.

---

## @apply policy

- Prefer utilities in Blade for one-offs  
- `@apply` only in `app.css` `@layer components` for repeated recipes (e.g. future `.nova-card`)  
- Do not `@apply` entire pages  

---

## Arbitrary values policy

| Allowed | Disallowed without review |
|---------|---------------------------|
| Rare `z-[60]` until tokens land | `text-[#abc123]` brand one-offs |
| `grid-cols-[200px_1fr]` complex layouts | Random `w-[137px]` |
| SVG path data elsewhere | Arbitrary animation spam |

If needed thrice → token / theme extend.

---

## Forms plugin

Use Tailwind forms defaults; override focus ring to indigo/primary:

`focus:border-indigo-500 focus:ring-indigo-500`

---

## Dark mode

When enabled: `dark:` variants paired with `data-theme` / `class` strategy chosen in theme architecture. Do not half-implement dark on one page.

---

## Anti-patterns

- Inline `style=` for colors/spacing  
- Duplicate long class strings — extract component  
- `!important` via `!` utilities as habit  
- Fixed widths that break mobile  
