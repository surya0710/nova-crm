# Deliverable 8 — Table Standards

Standards for all data tables in NovaCRM.

---

## Anatomy

```
[ Filter bar ]
[ Bulk bar — conditional ]
┌─────────────────────────────────────────────┐
│ ☑ │ Col…     │ Col…        │ Col… │ ⋯      │ sticky header
├───┼──────────┼─────────────┼──────┼─────────┤
│   │          │             │      │         │
└─────────────────────────────────────────────┘
[ Pagination · density · count ]
```

---

## Sticky headers

- Header sticky under page header (`z-sticky`)  
- Horizontal scroll: first column optional sticky for entity name  
- Shadow when scrolled  

---

## Column management

- Default columns per module  
- User show/hide + reorder (persist per user+org+table id)  
- Reset columns  
- Required columns (name) cannot hide  

---

## Sorting

- Click header toggles asc/desc/none (or asc/desc only)  
- `aria-sort` on active column  
- Server-side for large sets  
- One primary sort unless advanced  

---

## Filtering

- Filter bar above table  
- Chips for active filters; clear all  
- Saved filters integration  
- URL-serializable filters preferred  

---

## Bulk actions

- Header checkbox: page select; optional “select all matching”  
- Bulk bar: sticky; shows count  
- Actions permission-filtered  
- Confirm destructive  

---

## Export

- Export current filters  
- Formats: CSV / Excel as supported  
- Async for large exports + notification  

---

## Density modes

| Mode | Row padding |
|------|-------------|
| Comfortable | Default |
| Compact | Reduced |

Persist with personalization.

---

## Row actions

- Primary click: open entity (row or name link)  
- Secondary: ⋯ menu (Edit, Archive, …)  
- Avoid more than one icon button without overflow  

---

## Selection

- Checkbox column when bulk exists  
- Selected row background `primary-50`  
- Keyboard: space toggles when row focused (where implemented)  

---

## Inline editing

- Opt-in per column  
- Edit affordance; Save/Cancel or blur-save with validation  
- Not default for complex entities  

---

## Responsive behavior

| Viewport | Behavior |
|----------|----------|
| Desktop | Full table |
| Tablet | Horizontal scroll + sticky name |
| Mobile | Card list pattern OR scroll with prioritized columns |

Do not shrink fonts unreadably.

---

## Empty states

- No data vs no filter matches (different copy/CTAs)  
- Loading skeletons, not “No data” flash  

---

## Performance

- Paginate (default 15–25)  
- Virtualize only when necessary  
- Avoid rendering hidden heavy cells  

---

## Accessibility

- `<table>` with `<th scope>`  
- Caption or `aria-label`  
- Sort buttons not click-only divs  
- Announce sort/filter changes  

---

## Anti-patterns

- Tables without headers  
- Horizontal scroll with no sticky identity column on wide datasets  
- Actions only in hover  
- Mixing aggregated dashboards into unfiltered mega-tables  
