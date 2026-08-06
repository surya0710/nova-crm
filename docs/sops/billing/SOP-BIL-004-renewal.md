# SOP-BIL-004 — Renewal

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-BIL-004 |
| **Title** | Renewal |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Billing |
| **Owner** | Billing Owner / CS |
| **Reviewer** | Finance |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Renew subscriptions on time with accurate pricing and entitlement continuity.

## Scope

- **In scope:** Renewal invoicing, term extension, and failure-to-renew escalation.
- **Out of scope:** CS commercial relationship motions detailed in SOP-CS-005.

## Preconditions

- [ ] Renewal date known (T-90 process started via CS)
- [ ] Pricing current

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Billing | Billing | Issue renewal invoice |
| CRM | CS/AE | Track renewal stage |

## Step-by-step Procedure

### 1. Align with CS

1. Follow [Renewal](../../customer-success/renewal.md) cadence (T-90/T-60/T-30).
2. Issue renewal invoice / collect payment method update.

### 2. Confirm continuity

1. On payment, extend term; keep entitlements continuous.
2. On failure, escalate CS churn prevention (SOP-CS-007) before cancellation path.

## Validation Checklist

- [ ] Renewal invoice issued on schedule
- [ ] Payment status tracked
- [ ] Term extended or save play active
- [ ] Entitlements uninterrupted when paid
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If renewed at wrong price, correct with credit/rebill; notify Finance and customer.

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
| **Previous SOP** | [SOP-BIL-003 — Downgrade](SOP-BIL-003-downgrade.md) |
| **Next SOP** | [SOP-BIL-005 — Trial Activation](SOP-BIL-005-trial-activation.md) |
| **Related SOPs** | [SOP-CS-005](../customer-success/SOP-CS-005-renewal.md), [SOP-CS-007](../customer-success/SOP-CS-007-churn-prevention.md) |
| **Related Documents** | [Renewal playbook](../../customer-success/renewal.md) |
| **Required Forms** | Renewal order / invoice |
| **Required Checklists** | T-90 renewal checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
