# Deliverable 11 — Navigation Components

Design specs for navigation UI. Behavior: [../product/workspace-navigation.md](../product/workspace-navigation.md), [../product/sidebar-blueprint.md](../product/sidebar-blueprint.md).

---

## Sidebar

| Prop | Spec |
|------|------|
| Width | 16rem / 4rem collapsed |
| Bg | `sidebar.bg` (slate-900 default) |
| Sections | Workspace selector · Favorites · Primary · Recents · Admin · User |
| Item | Icon `icon-md` + label; active state clear |
| Section label | xs uppercase muted |
| Scroll | Independent; thin scrollbar |

Collapsed: icons + tooltips / flyouts for nested.

---

## Workspace switcher

- Select or compact list at top of sidebar  
- Shows only permitted workspaces  
- Active workspace name always visible  
- Keyboard listbox pattern  

---

## Top navigation / chrome

- Search field or button opening palette  
- Help → Knowledge  
- Notifications bell + badge  
- User menu  

No module dump in top bar.

---

## Breadcrumbs

- `nav` aria-label="Breadcrumb"  
- Separators decorative  
- Current page not a link  
- Truncate long entity names  

---

## Tabs

- Underline style default for entity/secondary  
- `role="tablist"`; arrows  
- Overflow: scroll or More tabs  

---

## Entity navigation

Header + tabs + optional side meta; overflow ⋯ for rare actions.

---

## Context bar

- Soft strip under header when cross-workspace  
- Text: From {type} {name} · Back  
- Dismiss control  

---

## Favorites

- Star lists; max 5 in sidebar  
- Empty: hide  

---

## Pinned pages

- Distinct icon from favorites (pin vs star)  
- Role/org defined  

---

## Recent items

- 8–10 items; type icon + title  
- Clear all  

---

## Command palette

| Spec | Value |
|------|-------|
| Open | Ctrl/⌘K |
| z-index | `z-command` |
| Modes | Go to · Search · Actions |
| Focus | Trap; restore |
| Empty | Suggestions |

---

## Mobile drawer

- Full-height left drawer  
- Overlay dismiss  
- Accordion primary nav  
- Same components, stacked  

---

## Anti-patterns

- Duplicate Profile/Knowledge in sidebar Settings  
- Unlabeled icon-only nav on desktop expanded mode  
- Tabs that wrap into multi-line chaos — use overflow  
