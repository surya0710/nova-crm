# Component Catalog

Phase 14.1 Blade design-system catalog. Design intent: [../design/component-library.md](../design/component-library.md).

---

## UI primitives (`x-ui.*`)

| Component | Tag | Key props |
|-----------|-----|-----------|
| Button | `x-ui.button` | `variant` primary\|secondary\|ghost\|danger\|link · `size` sm\|md\|lg · `href` · `disabled` |
| Badge | `x-ui.badge` | `variant` neutral\|primary\|success\|warning\|danger\|info |
| Card | `x-ui.card` | slots `header`, `footer` · `padding` |
| Alert | `x-ui.alert` | `variant` · `title` |
| Avatar | `x-ui.avatar` | `name` · `src` · `size` |
| Empty state | `x-ui.empty-state` | `title` · `description` · slots `icon`, `actions` |
| Skeleton | `x-ui.skeleton` | `lines` |
| Loading | `x-ui.loading` | `label` |
| Page header | `x-ui.page-header` | `title` · `subtitle` · slots `breadcrumbs`, `actions` |
| Stat card | `x-ui.stat-card` | `label` · `value` · `hint` · `trend` |
| Metric card | `x-ui.metric-card` | `label` · `value` · `description` · slot `icon` |
| Tabs | `x-ui.tabs` | `tabs` array of `{label,href,active}` |
| Drawer | `x-ui.drawer` | `name` · `title` · `side` · event `open-drawer-{name}` |
| Modal | `x-ui.modal` | `name` · `title` · `maxWidth` · events `open-modal-{name}` |
| Dropdown | `x-ui.dropdown` | slots `trigger`, `content` · `align` · `width` |

---

## Forms (`x-forms.*`)

| Component | Tag |
|-----------|-----|
| Field wrapper | `x-forms.field` (`label`, `name`, `hint`, `required`) |
| Input | `x-forms.input` |
| Textarea | `x-forms.textarea` |
| Select | `x-forms.select` |
| Checkbox | `x-forms.checkbox` |
| Radio | `x-forms.radio` |
| Date picker | `x-forms.date-picker` |

---

## Navigation (`x-nav.*`)

| Component | Tag |
|-----------|-----|
| Sidebar | `x-nav.sidebar` |
| Sidebar link | `x-nav.sidebar-link` |
| Workspace switcher | `x-nav.workspace-switcher` |
| Breadcrumbs | `x-nav.breadcrumbs` (`items`) |
| Command palette | `x-nav.command-palette` |
| Global search | `x-nav.global-search` |

---

## Shell (`x-shell.*`)

| Component | Tag |
|-----------|-----|
| Header | `x-shell.header` |
| Context bar | `x-shell.context-bar` |
| Notification drawer | `x-shell.notification-drawer` |

---

## Page layouts (`x-layouts.*`)

| Layout | Tag | Use |
|--------|-----|-----|
| Workspace home | `x-layouts.workspace-home` | Home canvases |
| Entity listing | `x-layouts.entity-listing` | Index tables |
| Entity detail | `x-layouts.entity-detail` | Show pages |
| Entity form | `x-layouts.entity-form` | Shared create/edit shell |
| Create | `x-layouts.create` | Alias of entity-form |
| Edit | `x-layouts.edit` | Alias of entity-form |
| Settings | `x-layouts.settings` | Settings hub pages |
| Analytics | `x-layouts.analytics` | Report canvases |
| Dashboard | `x-layouts.dashboard` | Widget grids |

---

## Tables

| Component | Tag |
|-----------|-----|
| Table shell | `x-tables.table` (`columns`, `sticky`, `dense`) |
| Pagination wrapper | `x-tables.pagination` |
| Toolbar | `x-tables.toolbar` |

---

## Entity / activity / workspace (Phase 14.2)

| Component | Tag |
|-----------|-----|
| Entity header | `x-entity.header` |
| Entity section | `x-entity.section` |
| Definition list | `x-entity.definition-list` / `x-entity.definition-item` |
| Related list | `x-entity.related-list` |
| Timeline | `x-activity.timeline` / `x-activity.timeline-item` |
| Attention rail | `x-workspace.attention-rail` / `attention-item` |
| Quick actions | `x-workspace.quick-actions` |
| Home widget | `x-workspace.widget` |
| Form section | `x-forms.section` |
| Form footer | `x-forms.footer` |
| Empty preset | `x-ui.empty-state-preset` (`variant` leads\|search\|activities\|projects\|tasks\|portfolios\|programs\|risks\|issues\|reports\|resources\|milestones\|employees\|attendance\|leave\|recruitment\|candidates\|assets\|payroll\|performance\|documents\|organizations\|subscriptions\|providers\|tickets\|plans\|platform_audit\|users\|roles\|integrations\|api_tokens\|departments\|branches\|admin_audit\|settings\|modules\|security\|campaigns\|attribution\|analytics\|dashboards\|kpis\|ai_insights\|…) |

CRM reference patterns: [crm-reference-implementation.md](./crm-reference-implementation.md).  
Projects / EPM reference: [projects-workspace.md](./projects-workspace.md).  
HRMS / Recruitment reference: [hrms-workspace.md](./hrms-workspace.md).  
Platform Administration (SaaS console): [platform-administration.md](./platform-administration.md).

Organization Administration (tenant): [organization-administration.md](./organization-administration.md) — Admin home, modules/security/branding/developer, Admin* search providers, `AdminCommandProvider` palette group.

Marketing & Analytics (intelligence layer): [marketing-analytics-workspace.md](./marketing-analytics-workspace.md) — Marketing/Analytics homes, campaigns, attribution, providers, executive/CRM/Projects/HR analytics, AI insights, KPI library, reports center, Marketing/Analytics search & palette.

Production readiness (Phase 14.9): [../release/production-readiness.md](../release/production-readiness.md) — UX consistency, component stabilization notes, performance/a11y/security checklists, smoke tests. Legacy aliases (`x-primary-button`, `x-text-input`) remain for older views; new work and auth login use `x-ui.*` / `x-forms.*` exclusively.

---

## Usage example

```blade
<x-layouts.entity-listing title="Leads" subtitle="Pipeline intake">
    <x-slot:actions>
        <x-ui.button href="{{ route('leads.create') }}">New lead</x-ui.button>
    </x-slot:actions>
    <x-slot:filters>…</x-slot:filters>
    <x-tables.table :columns="['Name', 'Company', 'Status']" sticky>
        …rows…
    </x-tables.table>
</x-layouts.entity-listing>
```

Do not duplicate button/input markup in feature modules — extend this catalog instead.
