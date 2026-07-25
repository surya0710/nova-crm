# Program 15.8 — Pilot Customer Program & Operational Validation

**Status:** Executed (local / SOP validation)  
**Date:** 2026-07-25  
**Scope:** Prove commercial onboarding and operations without inventing production-infra results from XAMPP.

## How to run pilots locally

```bash
php artisan pilot:seed
# or
php artisan db:seed --class=PilotCustomerSeeder
```

All pilot owner passwords: `password`

## Evidence pack index

| Deliverable | Document |
|-------------|----------|
| 1 — Pilot Customer Profiles | [pilot-customer-profiles.md](./pilot-customer-profiles.md) |
| 2–4, 7 — Onboarding / licensing / RBAC / workspaces | [execution-log.md](./execution-log.md) |
| 5 — Organization upgrade | [execution-log.md](./execution-log.md#deliverable-5--organization-upgrade) |
| 6 — Data migration | [data-migration-validation.md](./data-migration-validation.md) |
| 8 — Deployment validation | [deployment-validation-report.md](./deployment-validation-report.md) |
| 9 — Operational validation | [operational-validation-report.md](./operational-validation-report.md) |
| 10 — Customer Acceptance Testing | [customer-acceptance-testing.md](./customer-acceptance-testing.md) |
| 11 — Performance | [performance-validation.md](./performance-validation.md) |
| 12 — Security | [security-validation.md](./security-validation.md) |
| 13 — Documentation | [documentation-validation.md](./documentation-validation.md) |
| 14 — Issue Register | [issue-register.md](./issue-register.md) |
| 15 — Launch Readiness | [launch-readiness-checklist.md](./launch-readiness-checklist.md) · [ga-recommendation.md](./ga-recommendation.md) |
| Risk Register | [risk-register.md](./risk-register.md) |
| Sample import datasets | [datasets/](./datasets/) |

## Related libraries

- SOPs: [`../sops/`](../sops/)
- Onboarding: [`../onboarding/`](../onboarding/)
- Operations: [`../operations/`](../operations/)
- Deployment: [`../deployment/`](../deployment/)
- Engineering readiness: [`../release/production-readiness.md`](../release/production-readiness.md)

## Engineering rules followed

- No new business modules
- Bug fixes / docs / operational tooling only
- Existing customer data preserved (`organization:upgrade` is additive/idempotent)
- Production deploy evidence deferred to real staging/production hosts
