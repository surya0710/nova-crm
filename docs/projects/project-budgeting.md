# Project Budgeting

## Overview
Project budgets with categorized line items (planned / actual / forecast). Organization budget categories are seeded as system defaults. Totals recalculate from items; variance above a configured threshold notifies stakeholders.

## Database Tables
| Table | Model | Purpose |
| --- | --- | --- |
| `budget_categories` | `BudgetCategory` | Org category catalog (name, slug, color, `is_system`) |
| `project_budgets` | `ProjectBudget` | Budget header (currency, totals, status, notes) |
| `budget_items` | `BudgetItem` | Line items linked to categories |

## Services
`BudgetService`:
- `seedDefaultCategories($organization)` — idempotent insert from `config('projects.default_budget_categories')` (fallback `DEFAULT_CATEGORIES`)
- `create($project, $data, $items, $actor)` / `update($budget, $data, ?$items, $actor)` — header + item sync; recalculates totals; fires update event; variance notification
- `recalculateTotals($budget)` — re-sums planned/actual/forecast/variance

**Item keys:** `id`, `name`, `planned`, `actual`, `forecast`, `budget_category_id`, `category_slug`, `currency`, `notes`, `sort_order`.

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.budgets.view` | View budgets |
| `projects.budgets.create` | Create budgets |
| `projects.budgets.update` | Update budgets / items |
| `projects.budgets.manage` | Full budget administration |

Project helpers: `viewBudgets`, `manageBudgets`.

## Workflow Events
| Trigger | Event |
| --- | --- |
| `project.budget.updated` | `ProjectBudgetUpdated` |

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | `GET/PUT projects/{project}/budgets` (show creates-or-updates via update action) |
| API | `api/v1/projects/{project}/budgets` show/update |

## UI
Blade under `resources/views/projects/budgets/`. Dashboard widgets: budget variance / budget health. Global search matches budgets with `projects.budgets.view`.

## Acceptance Notes
- Creating a budget seeds default categories if missing.
- Variance threshold defaults to `config('projects.budget_variance_threshold_percent')` (10).
- Audit via `Auditable` on `ProjectBudget` and `BudgetCategory`.
