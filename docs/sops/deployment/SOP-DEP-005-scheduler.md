# SOP-DEP-005 — Scheduler

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-DEP-005 |
| **Title** | Scheduler |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Deployment |
| **Owner** | DevOps / Backend Lead |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Ensure the Laravel scheduler runs every minute and scheduled jobs remain healthy.

## Scope

- **In scope:** Cron/Task Scheduler entry, heartbeat verification, and scheduled job inventory.
- **Out of scope:** Scheduler monitoring (SOP-MON-003) and release-time schedule changes.

## Preconditions

- [ ] Application deployed
- [ ] Host time synchronized

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| OS cron / Task Scheduler | DevOps | Install schedule entry |

## Step-by-step Procedure

### 1. Install schedule runner

Install a cron separate from the queue worker, with absolute paths, overlap protection, and persistent logging:

```cron
* * * * * /usr/bin/flock -n /home/ACCOUNT/tmp/nova-crm-schedule.lock /usr/local/bin/php /home/ACCOUNT/nova-crm/artisan schedule:run --no-interaction >> /home/ACCOUNT/logs/nova-crm-schedule.log 2>&1
```

Use [`deploy/cron/cpanel-plesk.cron.example`](../../../deploy/cron/cpanel-plesk.cron.example) and replace all paths. Do not invoke individual scheduled commands from OS cron.

### 2. Verify

1. Run `php artisan schedule:list`, then manually test with `php artisan schedule:heartbeat`.
2. Confirm Platform Monitoring reads a recent value from cache key `platform.scheduler.last_run`.
3. Wait two minutes and confirm `schedule:run` continues updating the heartbeat without manual intervention.
4. Review the dedicated scheduler cron log and spot-check critical scheduled jobs.

## Validation Checklist

- [ ] Cron entry present and enabled
- [ ] Cron uses absolute paths, overlap lock, and dedicated logging
- [ ] Heartbeat healthy in monitoring
- [ ] Critical jobs listed on ticket
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Re-enable cron; run missed critical jobs manually only with Tech Lead approval; document catch-up.

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
| **Previous SOP** | [SOP-DEP-004 — Queue Workers](SOP-DEP-004-queue-workers.md) |
| **Next SOP** | [SOP-DEP-006 — Cache](SOP-DEP-006-cache.md) |
| **Related SOPs** | [SOP-MON-003](../monitoring/SOP-MON-003-scheduler-monitoring.md) |
| **Related Documents** | [Queues and Scheduler](../../deployment/queues-and-scheduler.md), [Operations README](../../operations/README.md) |
| **Required Forms** | Scheduler change ticket |
| **Required Checklists** | Scheduler verification checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
