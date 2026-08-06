# SOP-CS-003 — Health Check

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-CS-003 |
| **Title** | Health Check |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Customer Success |
| **Owner** | Customer Success |
| **Reviewer** | CS Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Measure customer health periodically and act on red flags.

## Scope

- **In scope:** Health scoring cadence, signals, and escalation.
- **Out of scope:** Formal QBRs (SOP-CS-004).

## Preconditions

- [ ] Account live
- [ ] Usage metrics accessible

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| CS health tools / CRM | CS | Score account |

## Step-by-step Procedure

### 1. Run health check

Monthly (bi-weekly for pilots): login ratio, module adoption, open P1/P2, data hygiene.

Template: [Health checks](../../customer-success/health-checks.md).

Escalate red flags via SOP-CS-007 within 48 hours.

## Validation Checklist

- [ ] Health score current
- [ ] Red flags ticketed
- [ ] Customer contacted if yellow/red
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If metrics unavailable, perform qualitative check with admin and fix telemetry.

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
| **Previous SOP** | [SOP-CS-002 — Training](SOP-CS-002-training.md) |
| **Next SOP** | [SOP-CS-004 — Quarterly Review](SOP-CS-004-quarterly-review.md) |
| **Related SOPs** | [SOP-CS-007](SOP-CS-007-churn-prevention.md), [SOP-SUP-006](../support/SOP-SUP-006-sla-management.md) |
| **Related Documents** | [Health checks](../../customer-success/health-checks.md) |
| **Required Forms** | Health check form |
| **Required Checklists** | [Health checks](../../customer-success/health-checks.md) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
