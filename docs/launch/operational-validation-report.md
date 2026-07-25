# Deliverable 9 — Operational Validation Report

Runbooks exercised locally against [`../operations/`](../operations/) and maintenance SOPs.

| Runbook | SOP / Doc | Local exercise | Result |
|---------|-----------|----------------|--------|
| Backup | SOP-MNT-002, `backup-verification.md` | Procedure reviewed; file-level DB dump optional on local MySQL | Pass (process) |
| Restore | SOP-MNT-003 | Procedure reviewed — **do not** overwrite shared DB without explicit approval | Pass (process) |
| Queue restart | SOP-MNT-007 / DEP-004 | `php artisan queue:restart` | Pass (command) |
| Cache clear | SOP-MNT-005 / DEP-006 | `php artisan optimize:clear` / `cache:clear` | Pass (command) |
| Log review | SOP-MON-004 | `storage/logs` inspection procedure | Pass (process) |
| Health checks | SOP-MON-001 | `/up` route + daily checklist | Pass (process) |
| Monitoring | `monitoring-checklist.md` | Checklist reviewed | Pass (process) |
| Scheduled tasks | SOP-MON-003 / DEP-005 | `php artisan schedule:list` | Pass (command) |

## Notes

- Local queue workers are not a substitute for Supervisor-managed workers.
- Backup/restore evidence for GA must include a dated restore drill on the production backup target.
- Incident path remains [`../operations/incident-response-plan.md`](../operations/incident-response-plan.md) + SOP-SUP-002.
