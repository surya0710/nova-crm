# SOP-CS-007 — Churn Prevention

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-CS-007 |
| **Title** | Churn Prevention |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Customer Success |
| **Owner** | Customer Success |
| **Reviewer** | CS Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Detect churn risk early and run a save plan within 48 hours of red flags.

## Scope

- **In scope:** Red-flag monitoring, save plans, and escalation to leadership.
- **Out of scope:** Final cancellation and offboarding execution.

## Preconditions

- [ ] Red flag observed (declining logins, unpaid invoices, unresolved P2s, champion departure)

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| CRM / Support | CS | Open save plan |

## Step-by-step Procedure

### 1. Save plan

1. Escalate within 48 hours of red flags.
2. Build save plan with admin/executive sponsor.
3. Coordinate Support/Product for blockers; Billing for payment risk.
4. If unrecoverable, hand to SOP-BIL-007 / Offboarding.

See [Churn prevention](../../customer-success/churn-prevention.md).

## Validation Checklist

- [ ] Save plan opened within 48h
- [ ] Executive sponsor engaged or attempted
- [ ] Outcome recorded
- [ ] Cancellation path started only if needed
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If save plan stalls, escalate to CS Lead + Sales Director same day.

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
| **Previous SOP** | [SOP-CS-006 — Expansion Opportunity](SOP-CS-006-expansion-opportunity.md) |
| **Next SOP** | [SOP-BIL-007 — Subscription Cancellation](../billing/SOP-BIL-007-subscription-cancellation.md) |
| **Related SOPs** | [SOP-CS-003](SOP-CS-003-health-check.md), [SOP-OFF-001](../offboarding/SOP-OFF-001-subscription-closure.md) |
| **Related Documents** | [Churn prevention](../../customer-success/churn-prevention.md) |
| **Required Forms** | Save plan template |
| **Required Checklists** | Red-flag response checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
