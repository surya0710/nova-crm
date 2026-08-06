# Phase 10.3.7 — Indian Income Tax & TDS Engine Progress Report

## 1. Phase Summary

**Objective:** Build a complete Indian Income Tax (TDS) engine fully integrated with Payroll — configurable financial-year tax rules, annual projections, monthly TDS, investment declarations, proof verification, Form-16 foundation, reports, dashboard, APIs, metadata, workflows, RBAC, and audit.

**Scope completed:** Financial year versioning, tax slabs (old/new regime), regime selection history, salary projection, declaration + proof workflows, TDS calculation engine, payroll integration via `TdsCalculationService`, Form-16 data foundation, reports/export, dashboard widgets, REST APIs, metadata entities, workflow events, notifications, RBAC, audit logging, and feature tests.

**Overall implementation status:** **Complete — Indian Income Tax & TDS Engine**

**Depends on:** Phases 10.3.1–10.3.6 (Payroll foundation through enterprise enhancement). Payroll was **not** rebuilt; salary math remains in existing payroll services.

---

## 2. Architecture

```
Controllers / API Controllers
        ↓
Form Requests
        ↓
TaxFacadeService  (orchestration only)
        ↓
IncomeTaxService | TaxProjectionService | InvestmentDeclarationService
TaxProofService | TdsCalculationService | Form16Service
TaxDashboardService | TaxReportService
        ↓
StatutoryComplianceService  →  TdsCalculationService
        ↑
PayrollCalculationService   (consumes statutory merge; no duplicate tax math)
```

| Service | Responsibility |
|---|---|
| `TaxFacadeService` | Orchestration only |
| `IncomeTaxService` | FY config, slabs, regime history, annual slab tax |
| `TaxProjectionService` | Annual salary/tax projection |
| `InvestmentDeclarationService` | Declaration lifecycle |
| `TaxProofService` | Proof upload / verify / reject + audits |
| `TdsCalculationService` | Monthly TDS for payroll |
| `Form16Service` | Form-16 Part A/B foundation data |
| `TaxDashboardService` | Widgets |
| `TaxReportService` | Reports + CSV/XLSX/PDF export |

Engine versions bumped to `10.3.7` in `PayrollCalculationService`, `StatutoryComplianceService`, `TdsCalculationService`, and `IncomeTaxService`.

---

## 3. Tax Calculation Flow

```
1. Resolve TaxFinancialYear (by period date / active FY)
2. Resolve employee regime (history → statutory profile → FY default)
3. Project annual gross (paid YTD + remaining months × current gross)
4. Subtract standard deduction + approved declarations (old regime)
5. Apply FY slabs → rebate 87A → surcharge → cess
6. Monthly TDS = (annual liability − TDS already deducted) / remaining months
7. Persist TaxProjection + TdsMonthlyCalculation
8. StatutoryComplianceService merges TDS deduction line into payroll snapshot
```

No hardcoded slabs — seeded from `config/hrms.php` `statutory.default_india_configuration.tds` into `tax_slabs` per financial year.

---

## 4. Financial Year Versioning

Table `tax_financial_years` stores:

- code / label / assessment year
- effective start/end dates
- default regime
- `version` + `configuration` JSON
- `is_active`

Historical calculations remain tied to the FY + slab rows that were in force. Activating a new FY deactivates prior active rows without deleting history.

---

## 5. Work Packages Delivered

| # | Package | Status |
|---|---|---|
| 1 | Financial Year Configuration | Done |
| 2 | Tax Slabs (old/new) | Done |
| 3 | Tax Regime Selection + history | Done |
| 4 | Salary Projection | Done |
| 5 | Investment Declaration lifecycle | Done |
| 6 | Proof Verification + audits | Done |
| 7 | TDS Calculation Engine | Done |
| 8 | Payroll Integration | Done |
| 9 | Form 16 Foundation | Done |
| 10 | Reports (Excel/CSV/PDF) | Done |
| 11 | Dashboard widgets | Done |
| 12 | REST APIs | Done |
| 13 | Metadata entities | Done |
| 14 | Workflow events | Done |
| 15 | Dynamic RBAC | Done |
| 16 | Audit logging | Done |
| 17 | Notifications | Done |
| 18 | Documentation | Done |
| 19 | Tests | Done |

---

## 6. Declaration Lifecycle

```
Draft → Submitted → Verified
                 ↘ Rejected → (editable draft again)
```

Proof statuses: `uploaded → verified | partial | rejected` with `tax_proof_audits` history.

---

## 7. RBAC

| Permission | Purpose |
|---|---|
| `tax.view` | View FY, projections, declarations, TDS |
| `tax.manage` | Manage FY, regimes, declarations |
| `tax.verify` | Verify declarations and proofs |
| `tax.calculate` | Run projections / TDS |
| `form16.generate` | Generate Form-16 records |

Granted to HR (and `*` roles) via sync migration. Policies use `hasPermission` only.

---

## 8. Workflow Triggers

| Trigger | Event |
|---|---|
| `tax.declaration.submitted` | `TaxDeclarationSubmitted` |
| `tax.declaration.approved` | `TaxDeclarationApproved` |
| `tax.declaration.rejected` | `TaxDeclarationRejected` |
| `tax.proof.uploaded` | `TaxProofUploaded` |
| `tax.proof.verified` | `TaxProofVerified` |
| `tds.calculated` | `TdsCalculated` |

Registered in `AppServiceProvider` and documented in `config/hrms.php` → `workflow_triggers`.

---

## 9. Audit Events

- `tax_financial_year_created` / `tax_financial_year_activated`
- `tax_regime_changed`
- `tax_projection_calculated`
- `tds_calculated`
- Declaration / proof lifecycle audits via `Auditable` + proof audit table
- `form16_generated`

---

## 10. Database

Forward-only migrations:

- `2026_08_06_000100_create_income_tax_tds_tables.php`
- `2026_08_06_000101_sync_income_tax_tds_permissions.php`

Tables: `tax_financial_years`, `tax_slabs`, `employee_tax_regimes`, `tax_projections`, `tax_declarations`, `tax_declaration_items`, `tax_proofs`, `tax_proof_audits`, `tds_monthly_calculations`, `form16_records`.

---

## 11. APIs

`routes/api_income_tax.php` under `/api/v1/tax`:

- dashboard, financial-years, regimes, projections, declarations, proofs, tds, reports, form16

Web UI under `/hrms/payroll/tax/*`.

---

## 12. Metadata

Registered entities: `tax_declaration`, `tax_proof`, `tax_projection`, `tax_regime`, `tax_financial_year`.

---

## 13. Reports & Dashboard

**Reports:** TDS Register, Tax Projection, Employee Tax Summary, Declaration Status, Proof Verification, Form 16 Summary — export CSV / XLSX / PDF.

**Widgets:** Pending Declarations, Pending Proof Verification, Monthly TDS, Annual Tax Liability, Employees without Regime, Verification Status.

---

## 14. Testing

```bash
php artisan test --filter=HrmsIncomeTaxTdsTest
php artisan test --filter=HrmsPayrollStatutoryTest
```

Coverage includes:

- Schema / permissions / workflow triggers / metadata
- Slab tax (old + new + rebate)
- FY setup + regime selection
- Declaration + proof workflows + events
- Projection + monthly TDS
- Payroll integration (TDS engine, not deferred placeholder)
- Form-16 foundation, dashboard, reports UI, API, RBAC

---

## 15. Out of Scope (confirmed)

- GST, Corporate Tax, International Taxation
- Multi-country payroll
- Income Tax e-filing / TRACES / AIS / PAN verification APIs
- Form-16 PDF polish (data foundation only)

---

## 16. Final Verification Checklist

- [x] Indian Income Tax integrated into Payroll via dedicated tax services
- [x] Tax rules configurable by financial year (no hardcoded slabs in calc path)
- [x] Monthly TDS from projected annual income
- [x] Declarations + proof verification workflow-enabled
- [x] Payroll consumes TDS only through `TdsCalculationService`
- [x] Form-16 data foundation available
- [x] Organization-scoped, RBAC-protected, auditable, metadata-enabled, API-enabled
- [x] Forward-only migrations (no migrate:fresh)
