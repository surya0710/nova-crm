# Deliverable 17 — Design Quality Checklist

Use before merging any new or redesigned screen in Phase 14+.

---

## How to use

- Owner checks all applicable boxes.  
- Reviewer spot-checks Critical + Accessibility.  
- Fail any Critical → do not merge.

---

## Critical

- [ ] Uses an approved [page template](./page-templates.md) (or documented exception)
- [ ] Layout matches [layout-system.md](./layout-system.md) spacing rhythm
- [ ] Colors are semantic tokens — no random one-off palette
- [ ] Typography follows [typography.md](./typography.md) (Figtree, scale)
- [ ] Primary actions use standard Button variants (one primary per region)
- [ ] Permission-aware: unauthorized actions hidden/disabled correctly
- [ ] Loading state defined (skeleton/spinner)
- [ ] Error state defined (field/page/toast)
- [ ] Empty state defined (no data vs no filter matches)
- [ ] No raw 403 for normal nav misses when empty-state pattern applies

---

## Layout & structure

- [ ] Page has one `h1`
- [ ] Breadcrumbs on entity/create/edit (when in-app)
- [ ] Consistent page header (title + actions)
- [ ] Sidebar/nav highlights correct item
- [ ] Workspace context correct (or context bar if cross-workspace)
- [ ] No nested scroll traps
- [ ] Cards not overused (card = interaction/grouping need)

---

## Components

- [ ] Reuses library patterns ([component-library.md](./component-library.md))
- [ ] Tables follow [table-standards.md](./table-standards.md) if present
- [ ] Forms follow [form-standards.md](./form-standards.md) if present
- [ ] Dashboard widgets follow [dashboard-standards.md](./dashboard-standards.md) if present
- [ ] Modals/drawers: focus trap + Esc + restore focus
- [ ] Icons: outline set, correct size, a11y names

---

## Interaction

- [ ] Submit buttons prevent double-post
- [ ] Destructive actions confirmed
- [ ] Success feedback present
- [ ] Bulk flows have progress/summary when needed
- [ ] Hover not the only path to critical actions

---

## Accessibility

- [ ] Keyboard path works end-to-end
- [ ] Focus visible
- [ ] Labels on inputs
- [ ] Contrast AA for text/UI
- [ ] Status not color-only
- [ ] `prefers-reduced-motion` respected if animated
- [ ] Touch targets ≥ 44px on mobile layout

---

## Responsive

- [ ] Usable at 360px width
- [ ] Sidebar/drawer behavior correct below `lg`
- [ ] Tables/forms adapt per [responsive-strategy.md](./responsive-strategy.md)
- [ ] No horizontal page scroll (except intentional regions)

---

## Content & IA

- [ ] Labels match [product glossary](../product/product-glossary.md)
- [ ] Screen belongs to correct workspace/module
- [ ] Drill-downs and cancels go to sensible destinations

---

## Performance / motion

- [ ] No decorative infinite animation
- [ ] Motion within [motion-system.md](./motion-system.md) budgets
- [ ] Heavy charts deferred/cached appropriately

---

## Sign-off

| Role | Name | Date |
|------|------|------|
| Author | | |
| Reviewer | | |

**Exceptions** (must link to ADR or ticket):

```
—
```
