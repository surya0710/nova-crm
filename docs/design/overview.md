# Konnect Nex Enterprise Design System — Overview

**Phase:** 13.3 — Enterprise Design System & Component Blueprint  
**Status:** Authoritative visual & interaction reference  
**Scope:** Documentation only — no production UI, CSS, JS, or Blade changes

---

## Purpose

Define a unified visual language so every screen—CRM, Projects, HR, Marketing, Operations, Analytics, Administration, and future modules—feels like one product.

| Phase | Answers |
|-------|---------|
| **13.1** | How is the product organized? |
| **13.2** | How do users work day to day? |
| **13.3** | How does the product look and behave? |

Implementation begins in **Phase 14**, mapping these tokens and components onto Blade + Tailwind + Alpine (see [FRONTEND.md](../FRONTEND.md) and engineering standards in [../frontend/overview.md](../frontend/overview.md)).

---

## Design principles

| Principle | Meaning |
|-----------|---------|
| Enterprise-first | Clarity, trust, density — not marketing-site flair inside the app |
| Clean over decorative | Prefer structure and hierarchy over glow, gradients, and ornament |
| Density without clutter | Information-rich layouts with consistent spacing rhythm |
| Accessibility by default | WCAG 2.2 AA; keyboard and SR first-class |
| Consistent interactions | Same loading, errors, confirms, tables, forms everywhere |
| Progressive disclosure | Advanced controls behind overflow, More, Customize |
| Responsive by design | Desktop → mobile behaviors defined, not improvised |
| Keyboard friendly | Every primary flow completable without a pointer |
| Mobile capable | Drawer nav, stacked dashboards, usable touch targets |
| Performance conscious | Motion and chrome that do not block work |

---

## Stack alignment (current → target)

| Layer | Today | Design system stance |
|-------|-------|----------------------|
| Templates | Blade | Components documented as patterns; implement as Blade/`x-` components in Phase 14 |
| CSS | Tailwind 3 + forms plugin | Tokens map to CSS variables + Tailwind theme extend |
| JS | Alpine.js 3 | Interaction patterns assume Alpine/minimal JS |
| Font | Figtree | Official UI sans ([typography.md](./typography.md)) |
| Color | Slate neutrals + Indigo accents | Formalized in [color-system.md](./color-system.md) |

Landing/marketing pages may use richer visuals; **in-app chrome stays enterprise-clean**.

---

## Document map

| Document | Contents |
|----------|----------|
| [design-tokens.md](./design-tokens.md) | Platform-wide tokens |
| [color-system.md](./color-system.md) | Semantic & domain colors |
| [typography.md](./typography.md) | Type scale & usage |
| [layout-system.md](./layout-system.md) | Grid, spacing, shells |
| [component-library.md](./component-library.md) | Reusable UI components |
| [page-templates.md](./page-templates.md) | Standard page layouts |
| [interaction-patterns.md](./interaction-patterns.md) | Loading, feedback, shortcuts |
| [table-standards.md](./table-standards.md) | Data tables |
| [form-standards.md](./form-standards.md) | Forms & validation |
| [dashboard-standards.md](./dashboard-standards.md) | Widgets & homes |
| [navigation-components.md](./navigation-components.md) | Sidebar, chrome, palette |
| [iconography.md](./iconography.md) | Icons & illustrations |
| [motion-system.md](./motion-system.md) | Motion & timing |
| [accessibility.md](./accessibility.md) | A11y standards (design-level) |
| [responsive-strategy.md](./responsive-strategy.md) | Breakpoints & adaptation |
| [theme-architecture.md](./theme-architecture.md) | Light/dark, branding |
| [design-review-checklist.md](./design-review-checklist.md) | Screen QA checklist |

Related product docs: [../product/overview.md](../product/overview.md)

---

## Token → implementation path (Phase 14)

```
docs/design tokens
    → CSS custom properties (:root / [data-theme])
    → tailwind.config.js theme.extend
    → Blade component classes / @apply layers
```

Do not hard-code one-off hex values in new views once tokens exist.

---

## Quality bar

Every new screen must pass [design-review-checklist.md](./design-review-checklist.md) before merge in Phase 14+.

---

## Out of scope (Phase 13.3)

- Changing CSS, Blade, JS, or Tailwind config  
- Shipping a Storybook/Living styleguide UI (docs first; living kit later)  
- Rebranding production indigo overnight — tokens document the target system  

---

## Phase outcome

Konnect Nex has a complete Enterprise Design System: the single source of truth for visual and interactive decisions, ready for Phase 14 implementation alongside IA and workspace experience blueprints.
