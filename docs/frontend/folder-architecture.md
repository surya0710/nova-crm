# Deliverable 1 — Frontend Folder Architecture

Target frontend structure for Konnect Nex. Migrates incrementally from today’s flat module folders ([../FRONTEND.md](../FRONTEND.md)); do not big-bang move everything in one PR.

---

## Target tree

```
resources/
├── css/
│   ├── app.css                 # Tailwind entry + @layer components (tokenized)
│   ├── workflow.css            # Feature-scoped CSS (legacy/isolated)
│   └── themes/                 # Optional: theme variable sheets (Phase 14+)
│
├── js/
│   ├── app.js                  # Alpine start, global stores registration
│   ├── bootstrap.js            # Axios + CSRF
│   ├── stores/                 # Alpine.store modules (sidebar, palette, toasts)
│   ├── components/             # Rare Alpine data factories (kanban, gantt helpers)
│   └── features/               # Feature scripts (follow-up-alerts, workflow, …)
│
├── images/                     # Static images committed to repo (rare)
│   └── empty-states/
│
└── views/
    ├── layouts/
    │   ├── app.blade.php           # Authenticated shell
    │   ├── guest.blade.php
    │   ├── sidebar.blade.php       # → evolve to components/nav/*
    │   ├── workspace.blade.php     # Optional workspace chrome (Phase 14)
    │   └── platform.blade.php      # If not already under platform/
    │
    ├── components/
    │   ├── ui/                     # Design-system primitives
    │   │   ├── button/
    │   │   ├── badge/
    │   │   ├── card/
    │   │   ├── alert/
    │   │   ├── avatar/
    │   │   ├── modal/
    │   │   ├── drawer/
    │   │   ├── tabs/
    │   │   ├── tooltip/
    │   │   └── dropdown/
    │   ├── forms/
    │   │   ├── input/
    │   │   ├── select/
    │   │   ├── textarea/
    │   │   ├── checkbox/
    │   │   ├── field/
    │   │   └── … 
    │   ├── tables/
    │   ├── filters/
    │   ├── nav/
    │   │   ├── sidebar.blade.php
    │   │   ├── sidebar-link.blade.php
    │   │   ├── breadcrumbs.blade.php
    │   │   ├── workspace-switcher.blade.php
    │   │   └── command-palette.blade.php
    │   ├── feedback/               # flash, toast hooks
    │   ├── widgets/                # dashboard widget frames
    │   ├── charts/
    │   ├── kanban/
    │   ├── timeline/
    │   ├── comments/
    │   ├── attachments/
    │   └── domain/                 # entity-specific composites (lead-convert-modal, …)
    │
    ├── partials/                   # Non-component includes (rare)
    │
    ├── workspaces/                 # Workspace home views (Phase 14)
    │   ├── home.blade.php
    │   ├── crm.blade.php
    │   ├── projects.blade.php
    │   └── …
    │
    ├── {module}/                   # Feature pages (leads, hrms, projects, …)
    │   ├── index.blade.php
    │   ├── show.blade.php
    │   ├── create.blade.php
    │   ├── edit.blade.php
    │   └── partials/
    │
    ├── emails/
    ├── vendor/                     # pagination overrides if any
    └── …

app/View/Components/                # Class-based Blade components when needed
├── AppLayout.php
├── GuestLayout.php
└── …                               # Prefer anonymous components; class when logic heavy

public/
├── build/                          # Vite output (gitignored artifacts)
└── images/                         # Public static if required

docs/
├── design/                         # Visual system
├── product/                        # IA / UX
└── frontend/                       # This architecture
```

---

## Blade layouts

| Layout | Role |
|--------|------|
| `layouts/app` | Tenant authenticated shell (sidebar, header, main) |
| `layouts/guest` | Auth flows |
| `layouts/workspace` | Optional wrapper adding workspace context bar / home chrome |
| Platform / Careers | Separate layouts under their view namespaces |

---

## Shared partials vs components

| Use partial (`@include`) | Use component (`<x-…>`) |
|--------------------------|-------------------------|
| One-off page fragment | Reused UI with API (props/slots) |
| Heavy inline loops kept local | Design-system primitives |

Rule: **second reuse → extract component**.

---

## Assets

| Asset | Location | Notes |
|-------|----------|-------|
| CSS entry | `resources/css/app.css` | Only Vite CSS entry unless feature CSS justified |
| JS entry | `resources/js/app.js` | Register Alpine + stores |
| Feature JS | `resources/js/features/*` | Import from `app.js` or dynamic import |
| Icons | Inline SVG in Blade **or** shared icon component — avoid icon font sprawl |
| Images | `resources/images` → Vite, or `storage` for uploads | Optimize; prefer SVG empty states |
| Org logos | Public disk / storage | Existing `organization-logo` |

---

## Current → target mapping

| Today | Target |
|-------|--------|
| `components/*.blade.php` flat | Group under `ui/`, `forms/`, `nav/`, `domain/` |
| `layouts/sidebar.blade.php` | `components/nav/sidebar` (+ data from View Composer/service) |
| Module folders (`leads/`, …) | Keep; add `partials/` + shared listing/detail components |
| Ad-hoc JS in `resources/js/*.js` | `features/` + `stores/` |

---

## Rules

1. Do not put business SQL in Blade.  
2. Do not add new top-level CSS files without perf/review justification.  
3. Workspace homes live under `views/workspaces/` once Phase 14 starts.  
4. Platform and Careers stay isolated trees.  
5. Keep `x-cloak` patterns in `app.css`.  
