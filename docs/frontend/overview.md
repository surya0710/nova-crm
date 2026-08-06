# Konnect Nex Frontend Architecture — Overview

**Phase:** 13.4 standards + Phase 14.1 shell + Phase 14.2 CRM reference  
**Status:** Authoritative engineering reference for UI implementation  
**Scope:** Standards in this folder; production shell shipped in Phase 14.1; CRM foundation in Phase 14.2 (see [shell-implementation.md](./shell-implementation.md), [crm-reference-implementation.md](./crm-reference-implementation.md), [component-catalog.md](./component-catalog.md), [migration-progress.md](./migration-progress.md))

---

## Purpose

Translate Phases **13.1–13.3** (IA, workspace experience, design system) into practical standards for engineers building UI with:

| Layer | Technology |
|-------|------------|
| Templates | Blade (Laravel) |
| Styling | Tailwind CSS 3 + `@tailwindcss/forms` |
| Interactivity | Alpine.js 3 |
| Bundler | Vite 6 + `laravel-vite-plugin` |

There is **no React/Vue SPA**. See also [../FRONTEND.md](../FRONTEND.md) for run/build ops.

---

## Architecture principles

| Principle | Meaning |
|-----------|---------|
| Component-first | Prefer `x-*` Blade components over copy-paste markup |
| Composition over duplication | Slots + partials; extract at second use |
| Blade-first rendering | Server HTML is the source of truth |
| Alpine for lightweight interactivity | Menus, toggles, drawers, small client state — not a SPA |
| Tailwind utility consistency | Token-aligned utilities; no one-off CSS sprawl |
| Progressive enhancement | Core flows work without depending on fragile JS |
| Accessibility by default | Semantic HTML + patterns from design a11y docs |
| Mobile-first responsiveness | Base styles mobile; enhance at `md`/`lg` |
| Performance-conscious | Lean assets, deferred work, dashboard discipline |

**No new UI patterns** outside [../design/](../design/) and these frontend standards.

---

## Blueprint stack

| Phase | Doc root | Answers |
|-------|----------|---------|
| 13.1 | [../product/](../product/) | Product organization |
| 13.2 | [../product/](../product/) | Daily workspace UX |
| 13.3 | [../design/](../design/) | Look & feel |
| 13.4 | [./](./) | How engineers build it |

---

## Document map

| Document | Contents |
|----------|----------|
| [folder-architecture.md](./folder-architecture.md) | Frontend folder structure |
| [blade-components.md](./blade-components.md) | Blade component standards |
| [alpine-standards.md](./alpine-standards.md) | Alpine.js usage |
| [tailwind-standards.md](./tailwind-standards.md) | Tailwind conventions |
| [component-architecture.md](./component-architecture.md) | Engineering component org |
| [page-assembly.md](./page-assembly.md) | Page composition patterns |
| [performance.md](./performance.md) | Performance requirements |
| [accessibility-implementation.md](./accessibility-implementation.md) | A11y implementation rules |
| [responsive-implementation.md](./responsive-implementation.md) | Responsive implementation |
| [naming-conventions.md](./naming-conventions.md) | Naming standards |
| [testing-standards.md](./testing-standards.md) | Frontend QA |
| [code-review-checklist.md](./code-review-checklist.md) | PR review checklist |
| [migration-strategy.md](./migration-strategy.md) | Phase 14 rollout |

Phase report: [../P13_PHASE_13_4_PROGRESS.md](../P13_PHASE_13_4_PROGRESS.md)

---

## Non-negotiables for Phase 14+

1. Use approved page templates ([../design/page-templates.md](../design/page-templates.md)).  
2. Prefer shared components over page-local duplicates.  
3. Colors/spacing via design tokens / approved Tailwind mappings.  
4. Pass [code-review-checklist.md](./code-review-checklist.md) and [../design/design-review-checklist.md](../design/design-review-checklist.md).  
5. Permission-aware UI — hide unauthorized actions.

---

## Out of scope (Phase 13.4)

- Implementing components or refactoring views  
- Changing Vite/Tailwind config  
- Introducing React/Vue  
- Rewriting the sidebar in this phase  

---

## Phase outcome

Konnect Nex has a complete Frontend Architecture and Implementation Standard. With 13.1–13.4, the platform has IA, workspace UX, design system, and engineering build standards — the foundation for Phase 14 UI work.
