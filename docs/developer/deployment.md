# Deployment

Canonical production runbook: [../deployment/overview.md](../deployment/overview.md).

Also see:

- [../../UPGRADE.md](../../UPGRADE.md)
- [../release/production-readiness.md](../release/production-readiness.md)
- [../release/checklist.md](../release/checklist.md)
- [../release/smoke.md](../release/smoke.md)

## Quick checklist

### Pre-deployment
- Validate migrations and config changes
- Confirm rollback plan
- Communicate release window

### Post-deployment
- Run smoke tests (`php artisan test --group=smoke` + manual checklist)
- Monitor logs, queue failed jobs, and Platform → Monitoring
- Record release outcome
