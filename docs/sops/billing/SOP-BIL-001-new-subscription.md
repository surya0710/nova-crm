# SOP-BIL-001 — New Subscription

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-BIL-001 |
| **Title** | New Subscription |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Billing |
| **Owner** | Billing Owner |
| **Reviewer** | Finance |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Activate a new paid subscription aligned to the signed Order Form.

## Scope

- **In scope:** Commercial subscription creation, billing contact, term start, and entitlement handoff to Platform.
- **Out of scope:** Technical org provisioning steps already covered in Onboarding (still coordinated here).

## Preconditions

- [ ] Signed Order Form
- [ ] Billing contact and payment method known

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Billing / subscriptions | Billing | Create subscription |
| Platform | Platform Operator | Mirror entitlements (SOP-ADM-003) |

## Step-by-step Procedure

### 1. Create commercial subscription

1. Enter plan, seats, term, and billing contact from Order Form.
2. Set start date; generate first invoice per payment terms.

### 2. Sync entitlements

1. Hand off to Platform for SOP-ADM-003 / SOP-ONB-003.
2. Confirm customer access matches purchase.

## Validation Checklist

- [ ] Subscription active in billing system
- [ ] Invoice generated or scheduled
- [ ] Entitlements match Order Form
- [ ] AE/CS notified
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Void incorrect subscription before entitlement sync if possible; issue credit memo if invoiced in error with Finance approval.

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
| **Previous SOP** | [SOP-SAL-006 — Contract Execution](../sales/SOP-SAL-006-contract-execution.md) |
| **Next SOP** | [SOP-BIL-002 — Upgrade](SOP-BIL-002-upgrade.md) |
| **Related SOPs** | [SOP-ADM-003](../administration/SOP-ADM-003-subscription-assignment.md), [SOP-ONB-003](../onboarding/SOP-ONB-003-module-licensing.md) |
| **Related Documents** | [Pricing Guide](../../sales/pricing-guide.md) |
| **Required Forms** | Order Form |
| **Required Checklists** | New subscription activation checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
