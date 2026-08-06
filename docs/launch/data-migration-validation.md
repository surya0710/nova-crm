# Deliverable 6 — Data Migration Validation

## Scope

Import representative datasets for pilot go-live rehearsals.

## Platform capability (as shipped)

| Domain | Entity | Import Platform | Notes |
|--------|--------|-----------------|-------|
| CRM | Leads | Yes | CSV/XLSX via Lead Import |
| CRM | Customers | Yes | CSV/XLSX via Customer Import |
| CRM | Contacts | No dedicated entity | Contact fields map onto lead/customer name columns |
| HRMS | Employees / Departments / Leave | No CSV adapter | Seeded via `PilotCustomerSeeder` / UI; `EmployeeProvisioningService::provisionFromImport` is a service hook only |
| Projects | Projects / Milestones / Tasks | No CSV adapter | Permission flags exist; no Import Platform adapters |

**Gap logged:** [ISSUE-P15.8-001](./issue-register.md) — SOP-ONB-006 overstates HRMS/Projects spreadsheet import coverage.

## Sample datasets (CRM)

| File | Rows | Purpose |
|------|------|---------|
| [datasets/pilot-leads-sample.csv](./datasets/pilot-leads-sample.csv) | 5 | Lead import rehearsal |
| [datasets/pilot-customers-sample.csv](./datasets/pilot-customers-sample.csv) | 4 | Customer import rehearsal |

## Validation procedure (CRM)

1. Pre-import backup (SOP-MNT-002).
2. Login as Customer A or C owner (CRM licensed).
3. Upload sample CSV → preview → commit.
4. Confirm record counts, validation errors CSV (if any), duplicate handling.
5. Spot-check 2 records with customer admin.

## Expected results

| Check | Expected |
|-------|----------|
| Record counts | Match committed rows |
| Validation | Invalid emails / missing required fields rejected with row errors |
| Duplicates | Handled per Lead/Customer import rules |
| Error reporting | Downloadable error CSV from import session |
| Unlicensed org | Import UI/routes blocked for orgs without CRM |

## HRMS / Projects pilot data

Validated via seeder counts (not CSV import):

| Org | Employees | Leave balances | Projects | Milestones | Tasks |
|-----|-----------|----------------|----------|------------|-------|
| A | 8 | — | — | — | — |
| B | 8 | Yes | 2 | 3 each | 3 each |
| C | 8 | — | — | — | — |
| D | 8 | Yes | 2 | 3 each | 3 each |
| E | 8 | Yes | 2 | 3 each | 3 each |

Exact post-seed counts should be confirmed with:

```bash
php artisan tinker --execute="echo App\Models\Organization::where('slug','like','%')->count();"
```

Or SQL counts by `organization_id` after `pilot:seed`.
