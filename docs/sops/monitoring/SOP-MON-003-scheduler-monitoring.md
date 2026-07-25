# SOP-MON-003 — Scheduler Monitoring

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-MON-003 |
| **Title** | Scheduler Monitoring |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Monitoring |
| **Owner** | Ops / Backend |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Confirm the scheduler heartbeat and critical scheduled jobs run as expected.

## Scope

- **In scope:** Heartbeat checks and missed-job detection.
- **Out of scope:** Installing the cron entry (SOP-DEP-005).

## Preconditions

- [ ] Scheduler installed
- [ ] Monitoring signals available

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Monitoring / logs | Ops | Verify heartbeat |

## Step-by-step Procedure

### 1. Verify

1. Confirm schedule heartbeat recent.
2. Confirm critical jobs succeeded in last cycle.
3. Escalate misses via SOP-DEP-005 recovery / incident path.

## Validation Checklist

- [ ] Heartbeat fresh
- [ ] Critical jobs OK or ticketed
- [ ] Evidence logged
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Re-run missed critical jobs only with Tech Lead approval; document catch-up.

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
| **Previous SOP** | [SOP-MON-002 — Queue Monitoring](SOP-MON-002-queue-monitoring.md) |
| **Next SOP** | [SOP-MON-004 — Error Log Review](SOP-MON-004-error-log-review.md) |
| **Related SOPs** | [SOP-DEP-005](../deployment/SOP-DEP-005-scheduler.md) |
| **Related Documents** | [Monitoring checklist](../../operations/monitoring-checklist.md) |
| **Required Forms** | Scheduler alert ticket |
| **Required Checklists** | Critical job inventory checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
