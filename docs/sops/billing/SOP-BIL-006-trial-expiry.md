# SOP-BIL-006 — Trial Expiry

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-BIL-006 |
| **Title** | Trial Expiry |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Billing |
| **Owner** | Billing / Sales Ops |
| **Reviewer** | Sales Manager |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Expire or convert trials on the end date without silent paid entitlement.

## Scope

- **In scope:** Expiry notices, conversion to paid, or disablement of trial access.
- **Out of scope:** Paid cancellation and full offboarding.

## Preconditions

- [ ] Trial end date reached or conversion decision made

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Billing / Platform | Billing / Platform Operator | Expire or convert |

## Step-by-step Procedure

### 1. Pre-expiry

1. Notify prospect at T-7 and T-1.
2. If converting, run SOP-BIL-001 with Order Form.

### 2. At expiry without conversion

1. Disable paid-grade entitlements / suspend org per policy.
2. Retain data per retention SOP until deleted or converted.

## Validation Checklist

- [ ] Notices sent
- [ ] Converted or access reduced on time
- [ ] No unpaid entitlements left active
- [ ] Owner updated CRM stage
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If expired incorrectly while Order Form in flight, restore trial/paid access with Sales Manager approval.

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
| **Previous SOP** | [SOP-BIL-005 — Trial Activation](SOP-BIL-005-trial-activation.md) |
| **Next SOP** | [SOP-BIL-007 — Subscription Cancellation](SOP-BIL-007-subscription-cancellation.md) |
| **Related SOPs** | [SOP-BIL-001](SOP-BIL-001-new-subscription.md), [SOP-OFF-001](../offboarding/SOP-OFF-001-subscription-closure.md) |
| **Related Documents** | [Pricing Guide](../../sales/pricing-guide.md) |
| **Required Forms** | Trial expiry notice template |
| **Required Checklists** | Trial expiry checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
