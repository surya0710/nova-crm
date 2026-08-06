# Deliverable 6 — Personalization

User and org personalization strategy for Konnect Nex daily experience.

---

## Principles

1. **Personalization never overrides security** — permissions and plan gates win.  
2. **Defaults first** — good role defaults beat empty custom layouts.  
3. **Reset always available** — one click back to role default.  
4. **Scoped storage** — preferences are per user **and** organization.  
5. **Progressive** — power features (density, pin dashboards) appear after basics.

---

## Personalization catalog

| Preference | Scope | Default | Notes |
|------------|-------|---------|-------|
| **Favorite modules** | User+Org | Role-based | Shortcut chips on Home |
| **Favorite pages** | User+Org | Empty | Sidebar Favorites |
| **Favorite entities** | User+Org | Empty | Star on records |
| **Pinned dashboards** | User+Org | Role landing | Quick jump to exec/dept dashboards |
| **Pinned widgets** | User+Org | — | Keep widget visible when resetting others |
| **Recent items** | User+Org | Auto | See navigation |
| **Workspace preferences** | User+Org | Last used | Last workspace, collapsed groups |
| **Landing page preference** | User+Org | Persona default | Home vs CRM vs … |
| **Widget layout** | User+Org+Dashboard | Role layout | Positions/sizes |
| **Theme** | User (optional Org force) | System/Light | Dark only if product supports; no purple-default mandate |
| **Density** | User+Org | Comfortable | Comfortable · Compact |
| **Locale / time format** | User inherits Org | Org | Profile may override time format later |
| **Notification prefs** | User | Sensible on | See notifications doc |
| **Search prefs** | User | Everywhere | Default scope |

---

## Favorite modules

- User marks modules (Leads, Leave, …) as favorites.  
- Shown as chips on Personal Home and in palette.  
- Does not grant access — only shortcuts.

---

## Favorite pages & entities

| Type | Example |
|------|---------|
| Page | Reports → Finance |
| Entity | Customer Acme Corp |
| Hub | Recruitment → Candidates |

Star control on page header; sync to sidebar Favorites.

---

## Pinned dashboards

Users with access to multiple dashboards (e.g. Projects + Executive) may pin up to **3** for quick switcher under workspace header.

Org admins may suggest pins by role (Pinned pages).

---

## Pinned widgets

In Customize mode, “Pin widget” prevents removal on “Reset optional widgets”. Mandatory org widgets are implicitly pinned.

---

## Recent items

Automatic; user can disable “Show recents” in preferences (still tracks for palette optionally).

---

## Workspace preferences

| Key | Behavior |
|-----|----------|
| `last_workspace` | Restore on login |
| `nav_groups_open` | Which accordions expanded |
| `sidebar_collapsed` | Desktop collapsed mode |
| `home_attention_dismissed` | Temporary dismissals with TTL |

---

## Landing page preference

Options limited to workspaces/dashboards the user can access:

1. Persona default (system)  
2. Home  
3. Specific workspace home  
4. Specific pinned dashboard  

First login uses persona default ([user-personas.md](./user-personas.md)).

---

## Widget layout

- Drag/resize in Customize mode  
- Saved per dashboard id  
- “Reset to role default”  
- Export/import layouts out of scope for Phase 14 MVP  

Conflict order: Plan → Permission → Org → Role → User ([dashboard-blueprint.md](./dashboard-blueprint.md)).

---

## Theme & density

| Control | Values | Phase |
|---------|--------|-------|
| Theme | System · Light · Dark (if shipped) | 14+ |
| Density | Comfortable · Compact | 14 |
| Motion | Full · Reduced (follow OS) | 14 |

Org may lock theme for brand compliance.

---

## Preference UI

| Entry | Contents |
|-------|----------|
| **User menu → Preferences** | Landing, density, theme, recents, notification shortcuts |
| **Customize on dashboard** | Widget layout |
| **Entity star / page star** | Favorites |
| **Administration** | Role default layouts, org mandatory widgets |

---

## Data & privacy

- Store only IDs and layout JSON — not duplicated PII.  
- On role loss, prune inaccessible favorites silently.  
- On org leave, delete org-scoped prefs.

---

## Existing hooks

- `UserDashboardPreference`  
- `OrganizationDashboardWidget` / `OrganizationQuickAction`  
- Notification preference routes  

Phase 14 extends these rather than inventing a parallel store where possible.

---

## Anti-patterns

- Personalization panels with 50 toggles on first visit  
- Saving filters as “personalization” without naming them Saved Filters  
- Theme options that break brand logos  
- Forcing Dark mode as default  
