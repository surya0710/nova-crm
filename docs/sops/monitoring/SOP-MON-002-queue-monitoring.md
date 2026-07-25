# SOP-MON-002 — Queue Monitoring

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-MON-002 |
| **Title** | Queue Monitoring |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Monitoring |
| **Owner** | Ops / Backend |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Detect and escalate queue depth, age, and failure anomalies.

## Scope

- **In scope:** Queue depth/age dashboards, failed job rates, and escalation thresholds.
- **Out of scope:** Queue recovery actions (SOP-MNT-007).

## Preconditions

- [ ] Workers configured (SOP-DEP-004)
- [ ] Monitoring panel available

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Platform Monitoring / queue metrics | Ops | Observe queues |

## Step-by-step Procedure

### 1. Observe

1. Check depth and oldest job age.
2. Review failed job rate.
3. If thresholds exceeded, open ticket and follow SOP-MNT-007 / SOP-SUP-002 as severity warrants.

## Validation Checklist

- [ ] Metrics reviewed
- [ ] Thresholds documented
- [ ] Escalations opened when breached
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If false alarm, tune thresholds with Tech Lead; document change.

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
| **Previous SOP** | [SOP-MON-001 — Daily Health Check](SOP-MON-001-daily-health-check.md) |
| **Next SOP** | [SOP-MON-003 — Scheduler Monitoring](SOP-MON-003-scheduler-monitoring.md) |
| **Related SOPs** | [SOP-DEP-004](../deployment/SOP-DEP-004-queue-workers.md), [SOP-MNT-007](../maintenance/SOP-MNT-007-queue-recovery.md) |
| **Related Documents** | [Monitoring checklist](../../operations/monitoring-checklist.md) |
| **Required Forms** | Queue alert ticket |
| **Required Checklists** | Queue threshold checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
