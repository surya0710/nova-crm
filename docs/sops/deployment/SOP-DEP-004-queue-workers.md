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

1. Development uses `QUEUE_CONNECTION=database`; run `php artisan queue:work database --queue=default --sleep=1 --tries=3 --timeout=360`.
2. On Linux/VPS, install [`deploy/supervisor/nova-crm-worker.conf.example`](../../../deploy/supervisor/nova-crm-worker.conf.example). It runs four processes across `default,imports,exports,bulk,provisioning,mail` with `autostart`, `autorestart`, TERM shutdown, and a 390-second graceful stop window.
3. On cPanel/Plesk without Supervisor, install the bounded direct worker from [`deploy/cron/cpanel-plesk.cron.example`](../../../deploy/cron/cpanel-plesk.cron.example). It runs every minute with absolute paths, a non-blocking `flock`, `--stop-when-empty`, `--max-time=50`, and dedicated logging.
4. Keep `DB_QUEUE_RETRY_AFTER` or `REDIS_QUEUE_RETRY_AFTER` at 390 or otherwise safely above the 360-second worker timeout.

### 2. Operate

1. After every deploy run `php artisan queue:restart`; long-lived workers finish their current job, exit gracefully, and are replaced by Supervisor.
2. Confirm every Supervisor process returns to `RUNNING`, or confirm bounded cron invocations exit cleanly.
3. Run the queue canary and health checks in [Queues and Scheduler — Release 1.2.x](../../deployment/queues-and-scheduler.md).
4. Monitor pending depth, oldest age, failures, and worker logs via Platform → Monitoring and SOP-MON-002.

## Validation Checklist

- [ ] Worker process running under supervisor
- [ ] All configured queues have worker coverage
- [ ] Jobs processing (depth not monotonically growing)
- [ ] Queue canary processed without a new failed job
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
| **Related Documents** | [Queues and Scheduler](../../deployment/queues-and-scheduler.md), [Technical Operations (legacy family)](../technical-operations.md), [Deployment overview](../../deployment/overview.md) |
| **Required Forms** | Worker change ticket |
| **Required Checklists** | Queue worker health checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
