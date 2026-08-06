# Deliverable 3 — Widget Design Standards

Standards for all NovaCRM dashboard widgets. Compatible with the 12-column widths already used in `config/dashboard.php`.

---

## Size system

| Token | Width (cols) | Height (rows) | Typical content |
|-------|--------------|---------------|-----------------|
| **Small** | 3–4 | 2–3 | Single KPI, quick action card |
| **Medium** | 6 | 3–4 | Short list, donut/bar, dual KPI |
| **Large** | 8–12 | 4–6 | Table, multi-series chart, activity feed |

Rules:

- Widgets declare `allowed_sizes` and `default_size`.  
- Users resize only among allowed sizes.  
- Full-width (12) reserved for Welcome, major tables, activity.  
- Max **3 Large** widgets on Personal/Workspace defaults.

---

## Anatomy

```
┌─────────────────────────────────────┐
│ Icon  Title              ⋯  Refresh │
│ Optional subtitle / time range      │
├─────────────────────────────────────┤
│                                     │
│            Body                     │
│                                     │
├─────────────────────────────────────┤
│ Footer link: View all →             │
└─────────────────────────────────────┘
```

| Element | Required | Notes |
|---------|----------|-------|
| Title | Yes | Glossary term |
| Overflow ⋯ | Yes if configurable/removable | Hide, configure, open full |
| Body | Yes | One primary content type |
| Footer drill-down | Yes for lists/KPIs | Never dead-end |
| Time range | Optional | Last 7/30/90 days |

---

## Content types

### KPI

- One primary metric + optional delta (↑↓ vs prior period)  
- Sparkline optional on Medium+  
- Click → filtered list  
- Color: neutral default; semantic only for health/threshold  

### Chart

- Types: bar, line, donut, funnel (pipeline), heatmap (risks)  
- Legend ≤ 6 series  
- Empty chart uses empty-state pattern  
- Tooltip on hover/focus  
- No 3D, no decorative gradients that harm contrast  

### Table / list

- Max 5–8 rows in widget; “View all”  
- Columns: primary label + 1–2 attributes + status  
- Row click → entity  
- Sort fixed by relevance unless configured  

### Progress

- Percent or fraction (3/10 milestones)  
- Determinate bar; indeterminate only while loading  
- Label includes numeric value for a11y  

### Health indicator

- States: Healthy · Watch · At risk · Critical (or Green/Amber/Red)  
- Always include text label — color alone insufficient  
- Used for projects, portfolios, integrations  

### Card / action

- Single CTA (Mark Attendance, Approve)  
- Confirm destructive actions  
- Success toast + widget refresh  

---

## Card behavior

| Interaction | Behavior |
|-------------|----------|
| Hover | Subtle elevation/border; no layout shift |
| Focus | Visible focus ring on whole card and controls |
| Drag (edit mode) | Grab handle; announce position |
| Click body | Drill-down if single target; else ignore |
| Click footer | Navigate to full view |
| Overflow | Configure · Remove · Open in module |

Edit mode is explicit (Customize dashboard) — not accidental drag on view mode.

---

## Drill-down

Every widget defines:

1. **Primary target** — route + default filters  
2. **Empty target** — create or learn action  
3. **Permission miss** — hide widget (preferred) or disable with explanation  

Deep links must preserve workspace context when possible ([cross-workspace-experience.md](./cross-workspace-experience.md)).

---

## Loading states

| Phase | UI |
|-------|----|
| Initial | Skeleton matching size (not spinner-only) |
| Refresh | Inline subtle progress on header; keep stale data visible |
| Slow (>2s) | “Still loading…” + cancel if applicable |
| Error | Inline error + Retry; do not blank entire dashboard |
| Partial | Show available series; note truncated |

Skeleton respects reduced-motion (static placeholders).

---

## Permissions & plan gates

Evaluation order: plan module → permission slug → org enablement → render.

| Condition | Behavior |
|-----------|----------|
| No permission | Omit from catalog and layout |
| Plan locked | Omit (or upgrade CTA only in Admin catalog) |
| Data empty | Empty state inside widget |
| Provider error | Error state |

Never flash a widget then remove it after fetch failure due to 403 — gate before render.

---

## Configuration

| Level | Options |
|-------|---------|
| **Widget instance** | Size, position, time range, filters (e.g. my vs team) |
| **Org** | Enable/disable widget types, mandatory Attention widgets |
| **Role default** | Seeded layouts per persona |
| **User** | Layout preference |

Config UI lives in Customize panel + Administration for org defaults — not inside every widget body.

---

## Mapping existing widgets (examples)

| Slug (config) | Type | Default size |
|---------------|------|--------------|
| `welcome` | Card / message | Large (12×2) |
| `notifications` | List | Medium |
| `calendar` | List | Medium |
| `my_leads` | List | Medium |
| `pipeline` | Chart / KPI | Medium |
| `mark_attendance` | Action card | Small |
| `leave_balance` | KPI | Small |
| `recent_activities` | List / feed | Large |

New widgets must declare type + sizes in catalog metadata (Phase 14).

---

## Visual & content rules

- One idea per widget  
- Titles ≤ 40 characters  
- Numbers formatted per org locale/currency  
- Truncate long names with tooltip  
- No emoji in titles  
- Prefer glossary terms  

---

## Accessibility

- KPI text available to screen readers (not canvas-only)  
- Charts: data table alternative or summary  
- Contrast ≥ WCAG AA  
- Keyboard: Tab to card → Enter drills; ⋯ is separate stop  

See [accessibility.md](./accessibility.md).
