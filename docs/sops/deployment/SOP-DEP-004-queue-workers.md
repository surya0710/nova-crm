# SOP-DEP-004 — Queue Workers

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-DEP-004 |
| **Title** | Queue Workers |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Deployment |
| **Owner** | DevOps / Backend Lead |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Run and supervise queue workers so asynchronous jobs process reliably.

## Scope

- **In scope:** Worker process configuration, supervisor/service setup, and restart after deploy.
- **Out of scope:** Queue monitoring alerts (SOP-MON-002) and queue recovery (SOP-MNT-007).

## Preconditions

- [ ] Queue connection configured in environment
- [ ] Supervisor / systemd / Windows service available

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Worker host | DevOps | Manage worker process |

## Step-by-step Procedure

### 1. Configure worker

1. Process example: `php artisan queue:work --sleep=1 --tries=3 --timeout=360`
2. Supervise with Supervisor / Windows service / systemd so workers auto-restart.

### 2. Operate

1. After every deploy: `php artisan queue:restart`
2. Monitor depth via Platform → Monitoring and SOP-MON-002.

## Validation Checklist

- [ ] Worker process running under supervisor
- [ ] Jobs processing (depth not monotonically growing)
- [ ] Restart performed after deploy
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Stop failing workers; flush or retry failed jobs per SOP-MNT-007; escalate P1 if customer-facing jobs stalled.

## Exceptions

| Exception | Handling | Approver |
|-----------|----------|----------|
| None documented | Follow change-management exception path | Operations Lead |

## Audit Trail

Record the following for every execution:

| Field | Source |
|-------|--------|
| Date / time (UTC) | Ticket or change record |
| Operator | Authenticated user |
| Organization / environment | Ticket fields |
| Actions taken | Procedure steps completed |
| Evidence links | Attachments / URLs |
| Approval (if required) | Approver name + timestamp |

## Cross References

| Relation | Reference |
|----------|-----------|
| **Previous SOP** | [SOP-DEP-003 — Environment Configuration](SOP-DEP-003-environment-configuration.md) |
| **Next SOP** | [SOP-DEP-005 — Scheduler](SOP-DEP-005-scheduler.md) |
| **Related SOPs** | [SOP-MON-002](../monitoring/SOP-MON-002-queue-monitoring.md), [SOP-MNT-007](../maintenance/SOP-MNT-007-queue-recovery.md) |
| **Related Documents** | [Technical Operations (legacy family)](../technical-operations.md), [Deployment overview](../../deployment/overview.md) |
| **Required Forms** | Worker change ticket |
| **Required Checklists** | Queue worker health checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
