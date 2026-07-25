# Deliverable 14 — Accessibility Standards (Design System)

Design-system-level accessibility requirements. Extends [../product/accessibility.md](../product/accessibility.md).

---

## WCAG target

- **WCAG 2.2 Level AA** for authenticated app  
- Aim for AAA contrast on primary body text where practical  
- Legal/compliance pages follow same bar  

---

## Keyboard navigation

| Requirement | Detail |
|-------------|--------|
| Completeness | All creates, approves, navigates, menus |
| Order | Logical DOM order matches visual |
| Shortcuts | Documented; dismissible overlays via Esc |
| Skip link | First focusable |
| No trap | Except modal/drawer/palette |

---

## Focus management

- Visible focus ring: 2px primary + offset  
- Never remove outline without replacement  
- Open modal → focus first control or title (`aria-modal`)  
- Close → restore trigger  
- Route change → move focus to `h1` or main  

---

## ARIA guidance

| Pattern | Role / notes |
|---------|----------------|
| Sidebar | `navigation` + label |
| Tabs | `tablist` / `tab` / `tabpanel` |
| Modal | `dialog` + `aria-labelledby` |
| Menu | `menu` / `menuitem` or disclosure pattern |
| Live | Toasts `polite`; critical rare `assertive` |
| Icons | Decorative hidden; buttons named |

Prefer native elements (`button`, `a`, `table`) over role inventiveness.

---

## Contrast ratios

| Case | Min |
|------|-----|
| Normal text | 4.5:1 |
| Large text (≥18.5px bold / 24px) | 3:1 |
| UI components / icons | 3:1 |
| Disabled | Exempt but still perceivable when possible |

Check primary buttons (white on primary-600) and sidebar active states.

---

## Screen readers

- Unique page titles  
- Meaningful link text  
- Tables with headers  
- Charts: text summary  
- Status updates not color-only  

---

## Reduced motion

```css
@media (prefers-reduced-motion: reduce) {
  /* duration → 0 or fade-only */
}
```

Skeletons static; spinners paused.

---

## High contrast

- Support Windows HCM where feasible (borders remain visible)  
- Do not convey state only with pale backgrounds  

---

## Touch targets

- Minimum **44×44px** interactive  
- Spacing between hit targets ≥ 8px  

---

## Responsive accessibility

- Zoom 200% usable without loss of function  
- Reflow to single column  
- Horizontal table scroll has focusable region / instructions  

---

## Testing (Phase 14+)

- axe on Home, Listing, Detail, Modal, Palette  
- Keyboard script for approve-leave / create-lead  
- Spot-check NVDA/VoiceOver on forms  

---

## Anti-patterns

- `div` click targets without button role/keyboard  
- `aria-label` that disagrees with visible text  
- Focus styles only on `:focus-visible` so poor that mouse users never see issues — still require visible for keyboard  
