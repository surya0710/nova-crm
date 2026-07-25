# CRM Reference Implementation

Phases **14.2** (foundation + Leads) and **14.3** (full CRM entity migration) establish the canonical Enterprise UX patterns for all workspaces. Product blueprint: [../product/workspace-home-blueprints.md](../product/workspace-home-blueprints.md). Shell: [shell-implementation.md](./shell-implementation.md).

---

## Status

| Area | Status |
|------|--------|
| CRM Home / nav | Done (14.2) |
| Shared entity / table / form / timeline | Done (14.2) |
| Leads | Done (14.2 reference) |
| Customers | Done (14.3) |
| Opportunities (board + list) | Done (14.3) |
| Revenue (dashboard, quotes, invoices, payments, products) | Done (14.3) |
| Activities / Reports / Imports / Saved Views | Done (14.3) |
| Search + command palette | Done (14.2–14.3) |

Progress: [../P14_PHASE_14_2_PROGRESS.md](../P14_PHASE_14_2_PROGRESS.md), [../P14_PHASE_14_3_PROGRESS.md](../P14_PHASE_14_3_PROGRESS.md).

---

## Patterns

| Area | Pattern |
|------|---------|
| Workspace entry | `route('crm.home')` → `x-layouts.workspace-home` |
| Navigation | `config/navigation.php` `menus.crm` + workspace `route` |
| Listing | `x-layouts.entity-listing` + `x-tables.table` + filters slot |
| Detail | `x-layouts.entity-detail` + `x-entity.section` + `x-activity.timeline` |
| Create/Edit | `x-layouts.create` / `edit` + `x-forms.section` + `x-forms.footer` |
| Pipeline board | `?view=board` Kanban; DnD posts to `pipeline.stage.update` |
| Empty | `x-ui.empty-state-preset` |
| Search scopes | `all`, `leads`, `customers`, `opportunities`, `revenue`, `saved_views`, `activities` |
| Commands | `CrmCommandProvider` group `CRM` |

**Reference modules:** Leads (`resources/views/leads/*`), then Customers / Pipeline for entity + board patterns.

---

## Shared components (14.2)

### Entity (`x-entity.*`)

| Tag | Role |
|-----|------|
| `x-entity.header` | Title, badges, meta, actions |
| `x-entity.section` | Card section with optional header/footer |
| `x-entity.definition-list` / `definition-item` | Detail field grid |
| `x-entity.related-list` | Related records list + empty |

### Activity (`x-activity.*`)

| Tag | Role |
|-----|------|
| `x-activity.timeline` | Composer slot + vertical timeline |
| `x-activity.timeline-item` | Actor, timestamp, body |

### Workspace (`x-workspace.*`)

| Tag | Role |
|-----|------|
| `x-workspace.attention-rail` / `attention-item` | Priority rail |
| `x-workspace.quick-actions` | CTA chip row |
| `x-workspace.widget` | Home widget frame |

### Forms additions

| Tag | Role |
|-----|------|
| `x-forms.section` | Sectioned form block |
| `x-forms.footer` | Cancel + submit bar |

---

## CRM home data

`CrmWorkspaceHomeService` aggregates Leads, Customers, Opportunities, Tasks, Invoices/Payments and `UserUiPreference` pins/favorites. No new tables.

Widget personalization key: `user_ui_preferences.dashboard_layout['crm']` (reserved for layout editor).

---

## Migration checklist (other workspaces)

1. Keep controller/service/policy unchanged.  
2. Replace view chrome with `x-app-layout` + `x-layouts.*`.  
3. Use `x-ui.button` / `x-ui.badge` / `x-forms.*` / `x-tables.*`.  
4. Put notes/history in `x-activity.timeline`.  
5. Add breadcrumbs: Workspace Home → Section → Record.  
6. Wire empty states via presets.  
7. Do not invent entities outside the data model.

---

## Related docs

- [component-catalog.md](./component-catalog.md)  
- [migration-progress.md](./migration-progress.md)  
- [../P14_PHASE_14_2_PROGRESS.md](../P14_PHASE_14_2_PROGRESS.md)  
- [../P14_PHASE_14_3_PROGRESS.md](../P14_PHASE_14_3_PROGRESS.md)  
