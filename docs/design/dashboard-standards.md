# Deliverable 10 — Dashboard Standards

Visual/interaction standards for dashboards and workspace homes. Complements [../product/dashboard-blueprint.md](../product/dashboard-blueprint.md) and [../product/widget-standards.md](../product/widget-standards.md).

---

## Widget sizes

| Size | Cols | Rows | Use |
|------|------|------|-----|
| Small | 3–4 | 2–3 | KPI, action |
| Medium | 6 | 3–4 | Lists, small charts |
| Large | 8–12 | 4–6 | Tables, feeds, major charts |

Grid: 12-col; gap 16–24. Max 3 large on default personal/workspace layouts.

---

## Card hierarchy

| Level | Treatment |
|-------|-----------|
| Page | No card — canvas bg |
| Widget | Elevated surface, radius-xl, border subtle, shadow-sm |
| Nested | Muted surface, no extra shadow |
| Attention rail | Emphasized border or soft primary tint |

One idea per widget card.

---

## Charts

- Title + time range in header  
- Legend ≤ 6  
- Empty & error states inside widget  
- Color from chart palette  
- Data table alternative for a11y  

---

## KPIs

- Label caption + value dominant  
- Optional delta with success/danger  
- Click → filtered list  
- `tabular-nums`  

---

## Health indicators

Text + icon + semantic color; used in project/portfolio widgets.

---

## Progress

Determinate bars with numeric label; milestone fractions “3/8”.

---

## Tables (in widgets)

Max 5–8 rows; footer “View all”; compact density; no bulk select inside widget.

---

## Quick actions

Bar under header; max 5 + More; see [../product/quick-actions.md](../product/quick-actions.md).

---

## Activity feeds

Widget uses activity item component; day groups optional; footer to full feed.

---

## Attention rail

- Lists actionable items with count badges  
- Highest priority first  
- Empty: hide rail or show positive “You’re clear”  
- Desktop: side column xl; else top stack  

---

## Customization

- Explicit **Customize** mode  
- Drag handle + resize  
- Add from catalog (permissioned)  
- Reset to role default  
- Org-mandatory widgets locked  

---

## Header chrome

`{Workspace} · Last refreshed · Refresh · Customize`

---

## Loading / error

Per-widget skeleton and Retry; never fail the whole dashboard for one widget.

---

## Anti-patterns

- Widgets without drill-down  
- Decorative charts with no numbers  
- Auto-playing motion on KPI tiles  
- Embedding full create forms in widgets  
