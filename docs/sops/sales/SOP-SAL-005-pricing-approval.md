# SOP-SAL-005 — Pricing Approval

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SAL-005 |
| **Title** | Pricing Approval |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Sales |
| **Owner** | Sales Manager |
| **Reviewer** | Sales Director / Finance |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Authorize discounts and commercial exceptions before quotations are sent to prospects.

## Scope

- **In scope:** Discount tiers, free months, pilots, and custom commercial terms.
- **Out of scope:** Contract legal review and signature (SOP-SAL-006).

## Preconditions

- [ ] Draft proposal with requested discount documented
- [ ] Opportunity amount and term known

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| CRM Opportunity | AE / Manager | Log approval |
| Finance channel | Sales Director+ | For >20% or custom terms |

## Step-by-step Procedure

### 1. Apply approval matrix

| Discount / exception | Approver |
|----------------------|----------|
| 0–10% list | AE |
| 11–20% | Sales Manager |
| >20% or custom terms | Sales Director + Finance |
| Free months / pilots | Sales Director |

### 2. Record approval

1. Log approver, percentage, and rationale on the Opportunity before quotation send.
2. Proceed to quotation generation only after approval is recorded.

## Validation Checklist

- [ ] Approval logged on Opportunity
- [ ] Approver matches matrix
- [ ] Quotation not sent before approval when discount >10%
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If an unapproved discount was quoted, retract the quotation, notify Finance if needed, and re-issue at approved terms.

## Exceptions

| Exception | Handling | Approver |
|-----------|----------|----------|
| Multi-year prepay with unusual billing | Finance must approve payment schedule | Finance |

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
| **Previous SOP** | [SOP-SAL-004 — Proposal Creation](SOP-SAL-004-proposal-creation.md) |
| **Next SOP** | [SOP-SAL-006 — Contract Execution](SOP-SAL-006-contract-execution.md) |
| **Related SOPs** | [SOP-SAL-004](SOP-SAL-004-proposal-creation.md), [SOP-SAL-006](SOP-SAL-006-contract-execution.md), [SOP-BIL-001](../billing/SOP-BIL-001-new-subscription.md) |
| **Related Documents** | [Pricing Guide](../../sales/pricing-guide.md) |
| **Required Forms** | Discount approval note on Opportunity |
| **Required Checklists** | Approval matrix (inline) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
