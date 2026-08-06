# General Availability Recommendation

**Program:** 15.8 — Pilot Customer Program & Operational Validation  
**Date:** 2026-07-25  
**Recommendation:** **Conditional Approve for Application GA readiness**

## Summary

Konnect Nex application onboarding, module licensing, RBAC, organization upgrade, CRM import path, and operational SOP coverage have been exercised through five representative pilot organizations (`pilot:seed`) and the Launch Evidence Pack under `docs/launch/`.

No **Blocker** defects are open in the [issue register](./issue-register.md).

## Approve (application / commercial process)

- Five pilot profiles covering CRM-only, HRMS+Projects, growth stack, delivery stack, and full enterprise.
- Provisioning uses the same upgrade/module services as production platform flows.
- `organization:upgrade` remains idempotent and non-destructive.
- SOP library + onboarding/ops/deployment documentation is executable.
- Security and tenancy controls covered by existing Feature tests and licensing gates.

## Conditions before full Release 1.0 / infra GA

1. Complete staging or production deployment validation (TLS, workers, cron, mail, backups, monitoring) and attach evidence to [deployment-validation-report.md](./deployment-validation-report.md).
2. Close or accept [ISSUE-P15.8-001](./issue-register.md) by correcting SOP-ONB-006 scope language.
3. Execute signed CAT with at least one live pilot (or document formal waiver).
4. Record staging performance baselines against [performance-validation.md](./performance-validation.md).

## Decision

| Option | Select |
|--------|--------|
| Approved for GA (app + infra) | ☐ |
| **Conditional approve (app ready; infra + live CAT pending)** | ☑ |
| Not approved | ☐ |

**Rationale:** Product and operational process are commercially validated in a production-like application environment. Infrastructure evidence from XAMPP would not be representative; defer infra sign-off to the real host.

## Next steps

1. Run `php artisan pilot:seed` on the shared validation database.
2. Complete CAT checklists with pilot champions.
3. Schedule staging deploy using SOP-DEP-002.
4. Reconvene launch board with updated [launch-approval.md](./launch-approval.md).
