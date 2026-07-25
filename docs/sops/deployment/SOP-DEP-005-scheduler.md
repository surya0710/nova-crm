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

Cron: `* * * * * php /path/to/artisan schedule:run`

### 2. Verify

1. Confirm schedule heartbeat / scheduled jobs appear healthy in monitoring.
2. Spot-check critical jobs (backups, digests, billing tasks as applicable).

## Validation Checklist

- [ ] Cron entry present and enabled
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
| **Related Documents** | [Operations README](../../operations/README.md) |
| **Required Forms** | Scheduler change ticket |
| **Required Checklists** | Scheduler verification checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
