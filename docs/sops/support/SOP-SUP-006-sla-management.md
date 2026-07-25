# SOP-SUP-006 — SLA Management

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SUP-006 |
| **Title** | SLA Management |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Support |
| **Owner** | Support Lead |
| **Reviewer** | Operations Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Measure, meet, and remediate support SLA commitments.

## Scope

- **In scope:** SLA timers, breach handling, and reporting.
- **Out of scope:** Commercial SLA contract negotiation (Sales/Legal).

## Preconditions

- [ ] [SLA Matrix](../../support/sla-matrix.md) current
- [ ] Ticketing SLA fields enabled

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Support console SLA reports | Support Lead | Monitor breaches |

## Step-by-step Procedure

### 1. Operate to matrix

1. Apply priorities and targets from [SLA Matrix](../../support/sla-matrix.md).
2. On breach: notify Support Lead; document reason and recovery.
3. Review SLA performance weekly.

## Validation Checklist

- [ ] Priority matches matrix definition
- [ ] Breaches documented with reason
- [ ] Weekly review completed or scheduled
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If mis-prioritized, correct priority, notify customer of new target, and adjust staffing.

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
| **Previous SOP** | [SOP-SUP-005 — Customer Communication](SOP-SUP-005-customer-communication.md) |
| **Next SOP** | [SOP-MON-001 — Daily Health Check](../monitoring/SOP-MON-001-daily-health-check.md) |
| **Related SOPs** | [SOP-SUP-001](SOP-SUP-001-ticket-handling.md), [SOP-CS-003](../customer-success/SOP-CS-003-health-check.md) |
| **Related Documents** | [SLA Matrix](../../support/sla-matrix.md) |
| **Required Forms** | Breach report form |
| **Required Checklists** | Weekly SLA review checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
