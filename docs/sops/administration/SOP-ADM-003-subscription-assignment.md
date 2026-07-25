# SOP-ADM-003 — Subscription Assignment

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ADM-003 |
| **Title** | Subscription Assignment |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Administration |
| **Owner** | Platform Operator |
| **Reviewer** | Billing Owner |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Assign or update the technical subscription record to match commercial entitlements.

## Scope

- **In scope:** Plan assignment, entitlement sync, and seat limits on the organization.
- **Out of scope:** Invoice generation and commercial approval (Billing SOPs).

## Preconditions

- [ ] Commercial approval or Order Form
- [ ] Organization provisioned

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Platform subscriptions | Platform Operator | Assign plan |

## Step-by-step Procedure

### 1. Match commercial record

1. Read Order Form / billing ticket entitlements.
2. Assign plan and modules.
3. Set seats and term dates.

### 2. Confirm

1. Customer Admin sees correct modules.
2. Attach confirmation to ticket; notify Billing.

## Validation Checklist

- [ ] Entitlements match commercial record
- [ ] Customer can access purchased modules only
- [ ] Billing notified
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Revert plan/modules/seats to prior entitlement snapshot; notify AE and Billing.

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
| **Previous SOP** | [SOP-ADM-002 — Organization Administration](SOP-ADM-002-organization-administration.md) |
| **Next SOP** | [SOP-ADM-004 — Role Management](SOP-ADM-004-role-management.md) |
| **Related SOPs** | [SOP-ONB-003](../onboarding/SOP-ONB-003-module-licensing.md), [SOP-BIL-001](../billing/SOP-BIL-001-new-subscription.md), [SOP-BIL-002](../billing/SOP-BIL-002-upgrade.md) |
| **Related Documents** | [Pricing Guide](../../sales/pricing-guide.md) |
| **Required Forms** | Order Form / change order |
| **Required Checklists** | Entitlement verification checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
