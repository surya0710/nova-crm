# Phase 14.3 Progress — CRM Entity Migration & Workspace Completion

**Status:** Complete  
**Date:** 2026-07-24  
**Scope:** Migrate remaining CRM modules onto Phase 14.2 shared layouts/components. No new design-system architecture.

---

## Outcome

The CRM workspace is fully migrated to the Enterprise UX Platform. Customers, Opportunities (board + list), Revenue, Activities, Reports, Imports/Exports, and Saved Views adopt the same patterns as the Leads reference implementation.

Business logic, services (aside from thin CRM UI aggregations), APIs, policies, workflows, metadata, and schema are unchanged.

---

## Deliverables

| # | Deliverable | Status | Notes |
|---|-------------|--------|-------|
| 1 | Customers | Done | Index / show / create / edit / form |
| 2 | Opportunities | Done | Board (DnD → stage update) + list; show/create/edit |
| 3 | Revenue workspace | Done | `crm.revenue` dashboard + quotations / invoices / payments / products |
| 4 | CRM Activities | Done | List / Timeline / Calendar views |
| 5 | Reports | Done | `crm.reports` hub + analytics layouts on finance reports |
| 6 | Imports & Exports | Done | Lead/customer import wizard chrome + `crm.exports` |
| 7 | Saved Views | Done | Listing with visibility/entity badges |
| 8 | Search expansion | Done | revenue / saved_views / activities providers via SearchService |
| 9 | Command palette | Done | Customers, Opportunities, Revenue, Reports, Activities, Saved Views |
| 10 | Dashboard KPIs | Done | Outstanding AR + revenue summary widget on CRM home |
| 11 | Legacy cleanup | Done | CRM module pages no longer use legacy `header` slot chrome |
| 12 | Documentation | Done | This file + frontend docs |

---

## Key paths

### New routes
- `crm.revenue`, `crm.reports` (plus 14.2: `crm.home`, `crm.activities`, `crm.saved-views`, `crm.exports`)

### Controllers
- `app/Http/Controllers/Crm/CrmRevenueController.php`
- `app/Http/Controllers/Crm/CrmReportsController.php`
- `OpportunityController@index` — `view=board|list` + board grouping (UI only)
- `OpportunityController@updateStage` — redirects `back()` for board DnD

### Search
- `SearchService::{searchQuotations,searchInvoices,searchPayments}` public
- `SearchService::{searchSavedViews,searchCrmActivities}`
- Providers: `CrmRevenueSearchProvider`, `CrmSavedViewSearchProvider`, `CrmActivitySearchProvider`

### Views
- `resources/views/customers/*`, `pipeline/*`, `quotations/*`, `invoices/*`, `payments/*`, `products/*`
- `resources/views/crm/{revenue,reports,activities,home}.blade.php`
- `resources/views/imports/**`, `resources/views/reports/{index,finance}.blade.php`

---

## Verification

- `php artisan route:list --name=crm`
- `php artisan view:cache`
- Smoke: CRM home, Customers, Pipeline board DnD, Revenue dashboard, Activities tabs, search scopes, command palette

---

## Next waves

Projects / HRMS / Marketing / Analytics / Administration workspace migrations using the same CRM reference patterns.
