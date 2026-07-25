# SOP-MON-004 — Error Log Review

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-MON-004 |
| **Title** | Error Log Review |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Monitoring |
| **Owner** | Ops / Backend |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Review application error logs for new or spiking failure patterns.

## Scope

- **In scope:** Daily/shift review of `storage/logs` or aggregated logging, triage, and ticket creation.
- **Out of scope:** Log rotation configuration (SOP-MNT-006).

## Preconditions

- [ ] Log access available
- [ ] Noise baselines roughly known

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Logs / APM | Ops / Backend | Read errors |

## Step-by-step Procedure

### 1. Review

1. Scan for new exception signatures and spikes.
2. Correlate with deploys/releases.
3. File bugs or incidents; link release if regression suspected.

## Validation Checklist

- [ ] Review completed for shift/day
- [ ] New signatures ticketed
- [ ] Security-relevant errors escalated to SOP-SEC-004
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If log volume overwhelms, sample systematically and expand disk/retention via SOP-MNT-006.

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
| **Previous SOP** | [SOP-MON-003 — Scheduler Monitoring](SOP-MON-003-scheduler-monitoring.md) |
| **Next SOP** | [SOP-MON-005 — Performance Review](SOP-MON-005-performance-review.md) |
| **Related SOPs** | [SOP-MNT-006](../maintenance/SOP-MNT-006-log-rotation.md), [SOP-SUP-003](../support/SOP-SUP-003-bug-escalation.md) |
| **Related Documents** | [Monitoring checklist](../../operations/monitoring-checklist.md) |
| **Required Forms** | Error review notes |
| **Required Checklists** | Exception triage checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
