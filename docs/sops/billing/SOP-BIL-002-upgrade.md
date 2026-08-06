# SOP-BIL-002 — Upgrade

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-BIL-002 |
| **Title** | Upgrade |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Billing |
| **Owner** | Billing Owner |
| **Reviewer** | Finance / AE |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Process subscription upgrades (seats/modules/plan) with proration rules and entitlement sync.

## Scope

- **In scope:** Upgrade quoting, approval, billing change, and platform entitlement update.
- **Out of scope:** Expansion opportunity qualification by CS (SOP-CS-006) before commercial close.

## Preconditions

- [ ] Customer upgrade request or signed change order
- [ ] Pricing approval if discounted

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Billing | Billing | Change subscription |
| Platform | Platform Operator | Update entitlements |

## Step-by-step Procedure

### 1. Commercial change

1. Confirm target plan/modules/seats and effective date.
2. Apply proration per Pricing Guide / contract.
3. Invoice delta.

### 2. Entitlement sync

1. Update platform subscription (SOP-ADM-003).
2. Notify CS and Customer Admin.

## Validation Checklist

- [ ] Billing record updated
- [ ] Proration documented
- [ ] Entitlements live
- [ ] Customer notified
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Reverse upgrade with Finance approval; restore prior entitlements; credit as required.

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
| **Previous SOP** | [SOP-BIL-001 — New Subscription](SOP-BIL-001-new-subscription.md) |
| **Next SOP** | [SOP-BIL-003 — Downgrade](SOP-BIL-003-downgrade.md) |
| **Related SOPs** | [SOP-CS-006](../customer-success/SOP-CS-006-expansion-opportunity.md), [SOP-ADM-003](../administration/SOP-ADM-003-subscription-assignment.md) |
| **Related Documents** | [Pricing Guide](../../sales/pricing-guide.md) |
| **Required Forms** | Change order |
| **Required Checklists** | Upgrade checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
