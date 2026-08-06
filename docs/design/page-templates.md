# Deliverable 6 — Page Templates

Standard page templates. Every new screen picks a template before custom layout.

---

## Shared chrome

All authenticated templates include:

- App sidebar (+ workspace switcher target)  
- Optional top search / notifications / help / user  
- Main landmark with skip link  
- Permission-aware actions  

---

## 1. Workspace Home

**Regions:** Header · Attention · KPI strip · Quick Actions · Widget grid · Activity/Pins  

**Refs:** [../product/workspace-home-blueprints.md](../product/workspace-home-blueprints.md), [dashboard-standards.md](./dashboard-standards.md)

---

## 2. Dashboard (specialized)

Same as home with optional narrower purpose (e.g. Executive): fewer creates, larger charts.

Variants: Personal · Workspace · Department · Executive · Organization.

---

## 3. Entity Listing

```
Page header (title, primary CTA, overflow)
Filter bar (search, chips, saved views, density)
Table / Board / Calendar toggle (secondary nav)
Bulk action bar (appears on selection)
Pagination / footer counts
```

Empty: module empty state. Loading: table skeleton.

---

## 4. Entity Detail

```
Breadcrumbs
Identity header (name, status badges, primary CTA, ⋯)
Context bar (if cross-workspace)
Tabs (Overview | Activity | Files | …)
Main + optional side meta column
```

Unsaved guard on leave.

---

## 5. Create Form

```
Breadcrumbs → “New {Entity}”
Title
Form sections (cards or field groups)
Sticky footer: Cancel · Create
```

Width: `container-lg`. Success → detail or list per module pattern.

---

## 6. Edit Form

Same as create; primary **Save**; optional autosave indicator; danger zone separate card at bottom.

---

## 7. Configuration

```
Configuration Hub header
Group nav (cards or side local nav)
Section title + description
Settings fields / tables
Save bar
```

**Refs:** [../product/settings-architecture.md](../product/settings-architecture.md)

---

## 8. Reports

```
Report catalog or single report header
Parameter controls (date, owner, …)
Run / Export
Result table or visualization
```

---

## 9. Analytics

Workspace Analytics home template: KPI + chart grid + links to reports; no create-primary.

---

## 10. Kanban

```
Header + filters + “Add”
Horizontal scroll board
Columns with counts
Cards (kanban component)
```

Drag with keyboard alternative (move menu).

---

## 11. Calendar

```
Header (period switch, today)
Toolbar (month/week)
Grid
Event side panel / modal on select
```

---

## 12. Timeline / Gantt

```
Header + zoom
Left task list + right timeline canvas
Today marker
Legend
```

Performance: virtualize long ranges when implemented.

---

## 13. Portfolio

```
Portfolio header + health
KPI strip
Projects table / cards
Risks summary
Links to executive / forecasts
```

---

## 14. Profile

User profile (account) vs Employee profile (HR):

- Account: user menu template — personal fields, password, prefs  
- Employee: entity detail template within HR  

---

## 15. Settings (module-local)

Prefer Hub; if local: Configuration template subset with “Manage in Configuration” link.

---

## Template selection guide

| Need | Template |
|------|----------|
| Daily landing | Workspace Home |
| Browse records | Entity Listing |
| View/edit one record | Entity Detail / Edit Form |
| New record | Create Form |
| Org setup | Configuration |
| Board workflow | Kanban |
| Schedule | Calendar / Timeline |
| Insight | Reports / Analytics |

---

## Anti-patterns

- Inventing a new shell per module  
- Listing without filters or empty state  
- Detail pages without breadcrumbs  
- Config pages that look like marketing landing  
