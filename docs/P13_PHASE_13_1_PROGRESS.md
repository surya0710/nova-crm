# P13 Phase 13.1 — Information Architecture & Product Organization Blueprint

## Phase

Phase 13.1 — Information Architecture & Product Organization Blueprint

## Outcome

NovaCRM has an authoritative product IA blueprint: workspaces, module classification, screen ownership, navigation hierarchy, sidebar design, Configuration Hub, search behavior, dashboard ownership, personas, business journeys, terminology, navigation pain points, and a prioritized Phase 14 backlog.

**No production code, routes, or UI were modified.** Documentation only.

## Delivered

| Area | Status |
| --- | --- |
| Product audit of existing modules (CRM, Projects/EPM, HRMS, Recruitment, ESS, Marketing, Workflows, Metadata, Settings, Search, Knowledge, Platform, Careers) | Done |
| Workspace architecture (Home, CRM, Projects, HR, Marketing, Operations, Analytics, Administration + future Finance/Support/Assets) | Done |
| Module classification (Operational, Management, Analytics, Administration, Configuration, Knowledge, Automation, Integration) | Done |
| Screen inventory (purpose, owner, route, nav, priority, redesign flag) | Done |
| Navigation hierarchy + complete target nav tree | Done |
| Enterprise sidebar blueprint (workspace selector, favorites, pinned, recents, collapsed/mobile, role-aware) | Done |
| Settings → Configuration Hub architecture (groups A–K) | Done |
| Global search architecture (categories, ranking, permissions, cross-workspace, palette foundation) | Done |
| Dashboard ownership model (personal / operational / department / executive / workspace) | Done |
| User personas (14) with goals, nav, dashboards, permissions | Done |
| Primary business flows (revenue, talent, delivery, leave, automation routing) | Done |
| Navigation pain-point review with remediation map | Done |
| Product glossary (official terms + rename backlog) | Done |
| Phase 14 implementation backlog (themes A–H, sprint sequencing) | Done |
| Product docs index (`docs/product/overview.md`) | Done |

## Acceptance criteria

| Criterion | Status |
| --- | --- |
| Every existing module audited and classified | Met |
| Complete workspace architecture documented | Met |
| Every screen has owner and purpose | Met |
| Navigation hierarchy fully defined | Met |
| Sidebar blueprint finalized | Met |
| Settings consolidated into unified architecture | Met |
| Search behavior documented | Met |
| Dashboard ownership defined | Met |
| User personas and business journeys documented | Met |
| Navigation pain points identified with solutions | Met |
| Product terminology standardized | Met |
| Phase 14 backlog prioritized | Met |
| No production code modified | Met |

## Feature documentation

| Topic | Doc |
| --- | --- |
| Index / principles | [product/overview.md](product/overview.md) |
| Module audit | [product/module-audit.md](product/module-audit.md) |
| Workspaces | [product/workspaces.md](product/workspaces.md) |
| Module classification | [product/module-classification.md](product/module-classification.md) |
| Screen inventory | [product/screen-inventory.md](product/screen-inventory.md) |
| Navigation map | [product/navigation-map.md](product/navigation-map.md) |
| Sidebar blueprint | [product/sidebar-blueprint.md](product/sidebar-blueprint.md) |
| Settings / Configuration Hub | [product/settings-architecture.md](product/settings-architecture.md) |
| Search | [product/search-architecture.md](product/search-architecture.md) |
| Dashboard ownership | [product/dashboard-ownership.md](product/dashboard-ownership.md) |
| Personas | [product/user-personas.md](product/user-personas.md) |
| Business flows | [product/business-flows.md](product/business-flows.md) |
| Navigation review | [product/navigation-review.md](product/navigation-review.md) |
| Glossary | [product/product-glossary.md](product/product-glossary.md) |
| Phase 14 backlog | [product/phase14-backlog.md](product/phase14-backlog.md) |

## Audit sources (read-only)

| Source | Use |
| --- | --- |
| `resources/views/layouts/sidebar.blade.php` | Current tenant nav |
| `config/organization_settings.php` | Settings hub catalog |
| `config/dashboard.php` | Widgets, plan gates |
| `config/dynamic_rbac.php` | Permission groups, system roles |
| `app/Services/SearchService.php` | Search entity coverage |
| `routes/web.php`, `platform.php`, `careers.php` | Screen/route inventory |

## Key product decisions

1. **Workspaces** are the top-level IA; modules nest inside them.
2. **Projects / Portfolios / Programs / Resources** leave the CRM sidebar group.
3. **Tasks** move to Operations (or Home → My Work), not CRM.
4. **ESS** is My HR mode under HR, not a parallel top section forever.
5. **Configuration Hub** owns setup; Administration owns users/roles/billing.
6. **UI label** Custom Fields; **Metadata** remains the advanced/engineering term.
7. Empty future workspaces (Finance, Support, Assets) stay hidden until GA.

## Highest-severity gaps found (for Phase 14)

| Severity | Gap |
| --- | --- |
| P0 | Projects suite capability with second-class navigation |
| P0 | HR flat list overload |
| P0 | Settings duplicated across sidebar + hub |
| P1 | Portfolios/Programs/Risks/Assignments hidden from main nav |
| P1 | Global search weak on People/HR entities |
| P2 | Marketing has no workspace home |

## Next phase

Implement against [product/phase14-backlog.md](product/phase14-backlog.md):

- Sprint 1: Workspace switcher + nav split (CRM / Projects / HR) + demote competing dashboards  
- Sprint 2: Configuration Hub expansion + glossary renames  
- Sprint 3: Sidebar blueprint + search categories/People + command palette shell  
- Sprint 4: Role dashboards + Leave/Recruitment hubs + flow CTAs  

## Run

Documentation-only phase — no migrations, seeders, or feature tests required.

```bash
# Verify blueprint files present
ls docs/product
```

## Notes

- Blueprint is the authoritative reference for future UX/nav/dashboard work; do not invent alternate IA in implementation phases.
- Route names and RBAC slugs are unchanged; Phase 14 may re-skin navigation with redirects as needed.
- Platform console and Careers portal remain adjacent surfaces, not tenant workspaces.
- `organization_settings.future_modules.assets` stays hidden until production-ready.
