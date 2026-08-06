# Deliverable 4 — Grid & Layout System

Layout rules for Konnect Nex application shell and pages.

---

## 12-column grid

- Content grids (dashboards, bento) use **12 columns**.  
- Gutter: `space-4` (16px) default; `space-3` compact.  
- Widget spans: 3, 4, 6, 8, 12 (see product widget sizes).  
- Forms: single column on mobile; 6+6 or 4+8 on desktop when label/field side-by-side is needed (prefer stacked labels).

---

## App shell

```
┌──────── sidebar (fixed) ────────┬──────── main ────────────────┐
│ w-64 expanded / w-16 collapsed  │ top chrome (optional)        │
│                                 │ breadcrumbs                  │
│                                 │ page header                  │
│                                 │ content (scroll)             │
└─────────────────────────────────┴──────────────────────────────┘
```

| Part | Width / behavior |
|------|------------------|
| Sidebar expanded | **16rem (256px)** — current `w-64` |
| Sidebar collapsed | **4rem (64px)** |
| Main min width | Flexible; avoid horizontal page scroll |
| Content max | **80rem (1280px)** optional for readability on forms; lists/dashboards may go full main width |
| Large displays | Content can use full main; dashboards stretch to available |

---

## Container widths

| Token | Max width | Use |
|-------|-----------|-----|
| `container-sm` | 40rem | Narrow forms, confirm |
| `container-md` | 48rem | Settings sections |
| `container-lg` | 64rem | Standard forms |
| `container-xl` | 80rem | Detail + side panel |
| `container-full` | 100% | Tables, kanban, dashboards |

---

## Responsive breakpoints

| Name | Min | Shell |
|------|-----|-------|
| base | 0 | Drawer sidebar, stacked |
| sm | 640 | |
| md | 768 | Dual-ish forms |
| lg | 1024 | Persistent sidebar |
| xl | 1280 | Comfortable dual panels |
| 2xl | 1536 | Wide dashboards |

---

## Page spacing

| Region | Mobile | Desktop |
|--------|--------|---------|
| Page padding | 16 (`space-4`) | 24–32 (`space-6`–`8`) |
| Header → content | 16–24 | 24 |
| Between sections | 24–32 | 32 |
| Card internal | 16–24 | 20–24 |
| Stack (fields) | 16 | 16 |

---

## Section spacing

- One purpose per section ([product principles](../product/overview.md))  
- Section header + `space-4` + body  
- Dividers optional; prefer whitespace  

---

## Card spacing

- Gap between cards in grid: 16–24  
- Nested cards: avoid card-in-card; use muted surface  

---

## Workspace layouts

| Zone | Spec |
|------|------|
| Sidebar primary | Workspace nav |
| Main | Workspace home or module pages |
| Attention rail | Optional column ~280–320px on xl; stacks above on smaller |

See [../product/workspace-home-blueprints.md](../product/workspace-home-blueprints.md).

---

## Dashboard layouts

1. Header row  
2. KPI strip (flex / grid 2–6)  
3. Widget grid 12-col  
4. Secondary row (activity / pins)  

Customize mode: same grid with drag handles.

---

## Form layouts

| Pattern | Use |
|---------|-----|
| Single column stacked | Default |
| Two-column field grid | Address blocks, paired dates |
| Split view | List + preview (xl) |
| Multi-step | Stepper above; one section visible |

Max form width: `container-lg` unless table-heavy.

---

## Modal layouts

| Size | Width | Use |
|------|-------|-----|
| sm | 24rem | Confirm |
| md | 32rem | Simple form |
| lg | 48rem | Complex form |
| xl | 64rem | Rare |
| full | nearly viewport | Mobile sheet |

Padding `space-6`; footer actions right-aligned (primary rightmost).

---

## Drawer layouts

- Width 20–28rem desktop  
- Full-height; edge from right (detail) or left (nav)  
- Mobile: full width sheet  

---

## Entity detail layouts

```
Header (identity + actions)
Tabs
┌──────── main (2/3) ────┬── side (1/3) ──┐
│ fields / activity      │ meta / related │
└────────────────────────┴────────────────┘
```

Side panel collapses under main below `lg`.

---

## Anti-patterns

- Nested scroll areas fighting the main canvas  
- Fixed pixel pages that ignore sidebar  
- Full-bleed marketing heroes inside authenticated app  
- Cards wrapping every single field  
