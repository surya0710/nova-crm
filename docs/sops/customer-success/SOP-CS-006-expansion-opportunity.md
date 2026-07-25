# SOP-CS-006 — Expansion Opportunity

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-CS-006 |
| **Title** | Expansion Opportunity |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Customer Success |
| **Owner** | Customer Success |
| **Reviewer** | AE / Sales Manager |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Identify and hand off expansion opportunities with usage evidence; CS does not discount.

## Scope

- **In scope:** Expansion signal detection, business case, and AE handoff.
- **Out of scope:** Pricing approval and contracting (Sales/Billing).

## Preconditions

- [ ] Usage or stakeholder signal for seats/modules/support tier

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| CRM | CS | Create expansion opportunity for AE |

## Step-by-step Procedure

### 1. Qualify and hand off

1. Identify expansion (seats, modules, premium support) with evidence.
2. Hand to AE with usage proof — CS does not discount.
3. Track outcome; update health/renewal context.

See [Upsell](../../customer-success/upsell.md).

## Validation Checklist

- [ ] Opportunity created for AE
- [ ] Evidence attached
- [ ] No unauthorized discounting by CS
- [ ] Customer expectations set
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If premature expansion pitch damaged trust, pause and repair via CS Lead before retry.

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
| **Previous SOP** | [SOP-CS-005 — Renewal](SOP-CS-005-renewal.md) |
| **Next SOP** | [SOP-CS-007 — Churn Prevention](SOP-CS-007-churn-prevention.md) |
| **Related SOPs** | [SOP-BIL-002](../billing/SOP-BIL-002-upgrade.md), [SOP-SAL-005](../sales/SOP-SAL-005-pricing-approval.md) |
| **Related Documents** | [Upsell](../../customer-success/upsell.md) |
| **Required Forms** | Expansion brief |
| **Required Checklists** | Expansion evidence checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
