# SOP-CS-005 — Renewal

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-CS-005 |
| **Title** | Renewal |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Customer Success |
| **Owner** | Customer Success / AE |
| **Reviewer** | CS Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Drive commercial renewal readiness through the T-90/T-60/T-30 motion.

## Scope

- **In scope:** CS-owned renewal relationship tasks coordinating with Billing SOP-BIL-004.
- **Out of scope:** Invoice generation internals (Billing).

## Preconditions

- [ ] Renewal date known
- [ ] Health and QBR context available

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| CRM renewal pipeline | CS/AE | Advance stages |

## Step-by-step Procedure

### 1. Execute cadence

Follow [Renewal](../../customer-success/renewal.md) at T-90 / T-60 / T-30.
Coordinate Billing for invoicing (SOP-BIL-004). Engage churn prevention if risk rises (SOP-CS-007).

## Validation Checklist

- [ ] Cadence tasks completed
- [ ] Decision maker engaged
- [ ] Billing coordinated
- [ ] Outcome recorded (renew/expand/risk)
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If missed cadence, compress timeline with CS Lead and prioritize executive outreach.

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
| **Previous SOP** | [SOP-CS-004 — Quarterly Review](SOP-CS-004-quarterly-review.md) |
| **Next SOP** | [SOP-CS-006 — Expansion Opportunity](SOP-CS-006-expansion-opportunity.md) |
| **Related SOPs** | [SOP-BIL-004](../billing/SOP-BIL-004-renewal.md), [SOP-CS-007](SOP-CS-007-churn-prevention.md) |
| **Related Documents** | [Renewal](../../customer-success/renewal.md) |
| **Required Forms** | Renewal forecast note |
| **Required Checklists** | T-90/T-60/T-30 checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
