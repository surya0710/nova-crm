# Deliverable 16 — Theme Architecture

Theming, branding, and white-label readiness.

---

## Theme layers

```
Platform defaults (Konnect Nex tokens)
  → Organization branding (logo, accent)
    → User preference (theme mode, density)
```

Security/plan gates unchanged by theme.

---

## Light mode

**Default for in-app.**

- `bg.app` neutral-50  
- Surfaces white  
- Text neutral-700/800  
- Sidebar dark slate (enterprise contrast) **or** light variant if org chooses  
- Primary indigo-600  

---

## Dark mode

**Opt-in** (user or OS `prefers-color-scheme` when user selects System).

| Token | Dark |
|-------|------|
| App bg | slate-950 |
| Elevated | slate-900 |
| Border | slate-700 |
| Text | slate-100 |
| Muted | slate-400 |
| Primary | Adjust for contrast (indigo-400 links; buttons verified AA) |

Charts/status colors revalidated for dark backgrounds.

Do **not** ship dark-only as product default.

---

## Organization branding

| Asset | Behavior |
|-------|----------|
| Logo | Sidebar + login; light/dark variants preferred |
| Favicon | Org or platform fallback |
| Accent / primary | Optional override of `primary.*` scale from brand color |
| Company name | Sidebar text |

Store in existing org branding settings; expose as CSS variables on `html[data-org]`.

Constraints:

- Generated palette must pass contrast checks or fall back  
- Do not allow neon accents that break AA  
- Sidebar may keep dark shell while accent changes active states  

---

## Accent colors

- Org accent maps to `primary` or separate `accent` used for highlights  
- Secondary buttons stay neutral  

---

## Logo handling

- Max height ~32–40px in sidebar  
- SVG/PNG; constrain width  
- Fallback initials avatar  
- Login page: larger lockup OK  

---

## White-label readiness

| Capability | Phase posture |
|------------|---------------|
| CSS variable theming | Designed now; implement Phase 14+ |
| Custom domain | Platform concern (out of design tokens) |
| Remove “Konnect Nex” wordmark | Allow via org setting later |
| Custom login background | Optional org asset |
| Email templates branding | Separate but share palette |

Design system does not require per-tenant CSS builds — runtime variables preferred.

---

## Density & theme prefs

Stored per user+org: `theme=light|dark|system`, `density=comfortable|compact`.

---

## Implementation sketch

```html
<html data-theme="light" data-density="comfortable" style="--nova-color-primary-600: …">
```

Tailwind: use CSS variables in theme colors.

---

## Anti-patterns

- Hard-coded indigo in every Blade file after tokens ship  
- Per-page theme hacks  
- Marketing purple gradients forced into app shell via brand upload  
- Dark mode that inverts images incorrectly  

---

## Relationship to landing

Public marketing/landing may use expressive visuals ([app.css](../../resources/css/app.css) landing utilities). Authenticated app follows this theme architecture strictly.
