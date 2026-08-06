# Deliverable 2 — Dashboard Experience Blueprint

Dashboard philosophy for Konnect Nex. Complements [dashboard-ownership.md](./dashboard-ownership.md) (who owns what) with **how dashboards behave**.

---

## Philosophy

Dashboards answer one of five questions:

| Hierarchy level | Question |
|-----------------|----------|
| **Personal** | What do *I* need to do now? |
| **Workspace** | How is *this domain* doing for my role? |
| **Department** | How is *my team* doing? |
| **Executive** | How is *the business / portfolio* doing? |
| **Organization** | How is *the tenant* healthy (ops/admin)? |

Rules:

1. One primary dashboard per landing context — no competing “homes” in the sidebar.  
2. Dashboards **surface** work; lists/entities **do** work.  
3. Every widget has a drill-down.  
4. Defaults are role-smart; layout is user-personalizable within limits.  
5. Empty is instructional, never blank.

---

## Dashboard hierarchy

### 1. Personal Dashboard

| Field | Spec |
|-------|------|
| **Identity** | Home workspace landing |
| **Target users** | All |
| **Primary goals** | Prioritize my day; jump to assigned work |
| **Default widgets** | Welcome, Needs Attention, My Tasks, Notifications, Calendar, one role module widget |
| **Custom widgets** | From personal + permitted module catalogs |
| **Widget limits** | Max 10 visible; max 3 “large” |
| **Layout rules** | Attention rail left or top; KPI strip; grid 12-col |
| **Refresh** | Soft refresh 60s for counts; manual for heavy charts |
| **Personalization** | Full layout edit; set as landing |

### 2. Workspace Dashboard

| Field | Spec |
|-------|------|
| **Identity** | CRM / Projects / HR / … homes |
| **Target users** | Workspace personas |
| **Primary goals** | Domain pulse + primary create actions |
| **Default widgets** | Per [workspace-home-blueprints.md](./workspace-home-blueprints.md) |
| **Custom widgets** | Workspace catalog only (+ pinned cross-workspace exceptions) |
| **Widget limits** | Max 12; sectioned by overview / ops / insight |
| **Layout rules** | KPI strip mandatory; Quick Actions visible without scroll on desktop |
| **Refresh** | 60–120s for KPIs; charts on demand |
| **Personalization** | Layout + pin; cannot remove required Attention widget if org policy sets it |

### 3. Department Dashboard

| Field | Spec |
|-------|------|
| **Identity** | e.g. Manager Dashboard (`hrms.manager.dashboard`) |
| **Target users** | Department managers, team leads |
| **Primary goals** | Approvals, team load, exceptions |
| **Default widgets** | Team leave queue, attendance exceptions, direct-report tasks/projects |
| **Custom widgets** | Limited catalog |
| **Widget limits** | Max 8 |
| **Layout rules** | Queues before vanity charts |
| **Refresh** | 30–60s for queues |
| **Personalization** | Moderate; org can lock approval widgets |

### 4. Executive Dashboard

| Field | Spec |
|-------|------|
| **Identity** | Projects executive, Portfolio executive, Recruitment executive, Analytics overview |
| **Target users** | CEO, Dept heads, PMO, HR leadership |
| **Primary goals** | Trends, risk, exceptions — not data entry |
| **Default widgets** | Rollup KPIs, health, forecast, exception lists |
| **Custom widgets** | Executive catalog only |
| **Widget limits** | Max 10; prefer large charts |
| **Layout rules** | No create CTAs in primary header (use secondary) |
| **Refresh** | 5 min cache OK (`DASHBOARD_CACHE_TTL`) |
| **Personalization** | Light; org templates preferred |

### 5. Organization Dashboard

| Field | Spec |
|-------|------|
| **Identity** | Administration home |
| **Target users** | Org admin, Owner |
| **Primary goals** | Tenant health: users, integrations, security, billing |
| **Default widgets** | Seats, integration status, workflow failures, audit highlights, plan |
| **Custom widgets** | Admin catalog |
| **Widget limits** | Max 8 |
| **Layout rules** | Security/billing above marketing fluff |
| **Refresh** | 60s health; billing daily |
| **Personalization** | Low |

---

## Cross-cutting layout system

### 12-column grid

Aligned with existing `default_width` in `config/dashboard.php` (1–12).

| Size token | Width | Height (rows) | Use |
|------------|-------|---------------|-----|
| **Small** | 3–4 | 2–3 | KPI, single action |
| **Medium** | 6 | 3–4 | Lists, small charts |
| **Large** | 8–12 | 4–6 | Tables, major charts |

### Vertical rhythm

1. Header (title, last refreshed, Customize)  
2. Attention / KPI strip  
3. Primary grid  
4. Secondary grid (activity, pins)  

### Density

| Mode | Widget chrome | Row height |
|------|---------------|------------|
| Comfortable (default) | Full headers | Standard |
| Compact | Condensed headers | −20% |

---

## Refresh behavior

| Data class | Default | Mechanism |
|------------|---------|-----------|
| Counts / badges | 30–60s | Poll or echo |
| Lists (my tasks) | 60–120s | Poll |
| Aggregated charts | Cache TTL (config, often 300s) | Cache + manual refresh |
| Executive rollups | 5 min | Cache |
| On focus | Revalidate stale | Visibility API |

Manual **Refresh** control on every dashboard header.

---

## Personalization model

Conflict order (unchanged): **Plan → Permission → Org enablement → Role default → User preference**.

User may:

- Add/remove optional widgets  
- Resize within allowed size set  
- Reorder  
- Reset to role default  

User may not:

- Add widgets outside permission/plan  
- Exceed widget limits  
- Disable org-mandatory Attention widgets  

Storage: extend existing `UserDashboardPreference` / org widget tables conceptually (implementation Phase 14).

---

## Relationship to specialized dashboards

| Existing route | Hierarchy level | Surfacing |
|----------------|-----------------|-----------|
| `dashboard` | Personal | Home |
| `projects.dashboard` | Workspace | Projects |
| `projects.executive` | Executive | Projects / Analytics link |
| `portfolios.executive` | Executive | Projects |
| `hrms.dashboard` | Workspace | HR |
| `hrms.manager.dashboard` | Department | HR |
| `hrms.leave.dashboard` | Workspace subsection | HR → Leave |
| `hrms.recruitment.*` dashboards | Workspace / Executive | HR → Recruitment |
| `ess.dashboard` | Personal (HR-scoped) | My HR |
| `reports.finance` | Workspace analytics page | Analytics → Finance |
| `platform.dashboard` | Organization (platform) | Outside tenant |

---

## Anti-patterns

- Putting every module widget on Personal Home  
- Create forms embedded in executive dashboards  
- Widgets that 403 on click  
- Duplicate KPI meaning different numbers without definition tooltip  
- Auto-play or noisy motion on KPI tiles  
