# Deliverable 8 — Accessibility Implementation

Implementation rules derived from [../design/accessibility.md](../design/accessibility.md) and [../product/accessibility.md](../product/accessibility.md).

---

## Semantic HTML

| Prefer | Avoid |
|--------|-------|
| `<button>` for actions | `<div onclick>` |
| `<a href>` for navigation | Button that only navigates without href fallback |
| `<table>` for tabular data | Grid of divs pretending to be tables |
| `<nav>`, `<main>`, `<header>` | Unlabeled landmarks |
| One `<h1>` per page | Skipping heading levels arbitrarily |

---

## ARIA

- Use ARIA only when native semantics insufficient  
- Modals: `role="dialog"`, `aria-modal="true"`, `aria-labelledby`  
- Tabs: full tab pattern or progressive links  
- Icons decorative: `aria-hidden="true"`  
- Icon-only buttons: `aria-label`  
- Live regions for toasts: `aria-live="polite"`  

Existing `<x-modal>` must keep focus trap behavior when evolved.

---

## Keyboard navigation

| Component | Requirement |
|-----------|-------------|
| Sidebar / drawer | Tab order; Esc closes mobile |
| Dropdown | Arrow keys + Esc |
| Modal | Trap; Esc; return focus |
| Tabs | Arrows when tablist focused |
| Command palette | Trap; arrows; Enter; Esc |

Test without mouse before PR for interactive chrome.

---

## Focus management

- Visible `focus:ring-*` (indigo/primary) — never `outline-none` alone  
- After validation errors: focus first invalid field  
- After modal close: restore previously focused element  

---

## Screen reader support

- Flash/toast messages announced  
- Page title updates (`<title>` via Blade)  
- Sortable columns expose `aria-sort`  
- Chart alternatives provided  

---

## Reduced motion

```blade
{{-- Prefer CSS --}}
```

```css
@media (prefers-reduced-motion: reduce) {
  .motion-safe\:animate-pulse { animation: none; }
}
```

Use Tailwind `motion-reduce:` / `motion-safe:` utilities.

---

## Form accessibility

- `<x-input-label for="…">` associated  
- Errors via `aria-describedby` + `input-error`  
- Required `aria-required`  
- Fieldsets for radio groups  

---

## Interactive components checklist

Before merging a new interactive component:

- [ ] Keyboard operable  
- [ ] Focus visible  
- [ ] Name exposed to a11y tree  
- [ ] Disabled state not focus-confusing  
- [ ] Mobile hit target ≥ 44px  

---

## Anti-patterns

- `tabindex="1+"` soup  
- Positive tabindex reordering  
- CSS that removes focus rings globally  
- Status by color alone (`bg-red` dot without text)  
