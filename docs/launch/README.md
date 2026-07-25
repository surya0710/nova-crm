# Program 15 — Launch Readiness & Customer Success

Operational library that transitions NovaCRM from a completed product into a commercially deployable SaaS business.

**Scope:** processes, onboarding, demos, documentation, sales assets, training, production ops, and pilot rollout — **not** new application features.

## Directory map

| Path | Purpose |
|------|---------|
| [`../sops/`](../sops/) | Standard operating procedures |
| [`../onboarding/`](../onboarding/) | Customer onboarding playbooks and go-live |
| [`../demos/`](../demos/) | Demo environment, scripts, industry scenarios |
| [`../sales/`](../sales/) | Sales & marketing collateral |
| [`../customer-success/`](../customer-success/) | Adoption, QBR, renewal, churn prevention |
| [`../operations/`](../operations/) | Production operations checklists |
| [`../deployment/`](../deployment/) | Deployment and upgrade playbooks |
| [`../support/`](../support/) | Support, SLA, incident handling |
| [`../training/`](../training/) | Internal training curricula |
| [`./`](./) | Pilot program and launch approval |

## Acceptance criteria

- [x] Complete operational SOP library
- [x] Demo organization prepared (`php artisan demo:seed-presentation`)
- [x] Master demo script + industry scenarios
- [x] Customer documentation entry points
- [x] Sales assets
- [x] Internal training material
- [x] Production operations documented
- [x] Pilot customer program prepared

## How to use

1. Sales and CS teams start at [`../sops/README.md`](../sops/README.md).
2. Implementation uses [`../onboarding/go-live-checklist.md`](../onboarding/go-live-checklist.md).
3. Demo prep uses [`../demos/data-guide.md`](../demos/data-guide.md).
4. Launch gate uses [`launch-approval.md`](launch-approval.md).

## Related engineering docs

- [Deployment overview](../deployment/overview.md)
- [Production readiness (Phase 14.9)](../release/production-readiness.md)
- [Troubleshooting](../troubleshooting/overview.md)
