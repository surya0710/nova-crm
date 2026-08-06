# Deliverable 12 — Workspace Accessibility

Accessibility and inclusive UX requirements for workspace experience. Applies to Phase 14+ implementation.

---

## Goals

- WCAG **2.2 AA** target for tenant app chrome, dashboards, and primary flows  
- Keyboard-complete for all daily actions  
- Usable on mobile and desktop  
- Compatible with screen readers (landmarks, names, live regions)

---

## Keyboard navigation

| Area | Requirement |
|------|-------------|
| Skip link | “Skip to main content” as first focus |
| Sidebar | Tab through items; Enter activates; Esc closes mobile drawer |
| Workspace switcher | Arrow keys in listbox pattern |
| Dashboard Customize | Tab to widgets; move mode with announced position |
| Tabs (secondary/entity) | Arrow keys within `role="tablist"` |
| Command palette | Focus trap; arrows; Enter; Esc |
| Modals | Focus trap; Esc closes; return focus |
| Quick Actions | Tab stops; Enter activates |

No keyboard traps except intentional modal/palette.

---

## Focus order

Logical order:

1. Skip link  
2. Org / workspace controls  
3. Search  
4. Notifications / help / user  
5. Sidebar primary  
6. Main: breadcrumbs → title → actions → content  

Focus visible: **2px+** ring with sufficient contrast; never `outline: none` without replacement.

---

## Shortcuts

| Shortcut | Action | Discoverability |
|----------|--------|-----------------|
| `Ctrl/⌘ K` | Command palette | Tooltip + Knowledge |
| `Ctrl/⌘ /` | Focus search (optional) | Preferences list |
| `g` then `h` | Go Home (optional vim-style later) | Power-user docs |
| `Esc` | Close overlay | — |
| `?` | Shortcut cheatsheet (future) | — |

Do not steal browser/OS shortcuts. Allow disable in Preferences.

---

## Contrast & visuals

- Text/icon contrast ≥ **4.5:1** (normal text), **3:1** UI components  
- Status colors paired with text/icons (not color-only)  
- Charts: patterns or labels, not color alone  
- Error text adjacent to fields  
- Respect `prefers-reduced-motion`: no obligatory parallax; skeletons static  

Avoid defaulting to low-contrast slate-on-slate in dark sidebar — ensure active/hover AA.

---

## Responsive layouts

| Breakpoint | Behavior |
|------------|----------|
| Desktop | Sidebar + main |
| Tablet | Collapsible sidebar |
| Mobile | Drawer; stacked KPIs; Quick Actions sheet |

Touch targets ≥ **44×44px**. No horizontal page scroll except intentional tables with affordance.

Widgets reflow: Large → full width; Medium → stack.

---

## Screen readers

| Element | Spec |
|---------|------|
| Landmarks | `banner`, `navigation`, `main`, `complementary` (attention), `contentinfo` |
| Sidebar | `nav` aria-label includes workspace name |
| Live regions | Toast and badge updates `aria-live="polite"`; critical `assertive` sparingly |
| Widgets | Title as heading; KPI value in text |
| Charts | Summary + data table alternative |
| Icons | Decorative `aria-hidden`; actionable have names |

Page title updates on workspace/entity navigation.

---

## Mobile usability

- Drawer dismissible via overlay, button, Esc  
- Sticky primary CTA where forms require  
- No reliance on hover alone  
- Notification panel full-screen sheet on small viewports  

---

## Forms & errors

- Label every input  
- Describe errors with `aria-describedby`  
- Don’t rely on placeholder as label  
- Maintain focus on first error on submit  

---

## Testing expectations (Phase 14)

- Keyboard pass on Home, CRM list, Project entity, Leave approve  
- axe/lighthouse critical issues = 0 on chrome  
- Screen reader spot-check: Notifications, Palette, Dashboard  

---

## Anti-patterns

- Click-only overflow menus without keyboard  
- Disabling focus styles  
- Infinite carousel motion  
- Tiny badge-only status  
