# SOP-BIL-003 — Downgrade

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-BIL-003 |
| **Title** | Downgrade |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Billing |
| **Owner** | Billing Owner |
| **Reviewer** | Finance / CS |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Process subscription downgrades with effective-date rules and seat/module reductions.

## Scope

- **In scope:** Downgrade scheduling, data-impact warnings, billing adjustment, and entitlement reduction.
- **Out of scope:** Full cancellation (SOP-BIL-007) and offboarding.

## Preconditions

- [ ] Customer request in writing
- [ ] CS risk review for churn (SOP-CS-007)

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Billing | Billing | Schedule downgrade |
| Platform | Platform Operator | Reduce entitlements on effective date |

## Step-by-step Procedure

### 1. Assess impact

1. Confirm modules/seats removed and data implications.
2. Warn customer about feature loss; offer CS save path if appropriate.

### 2. Execute on effective date

1. Adjust billing.
2. Reduce platform entitlements; confirm access.

## Validation Checklist

- [ ] Effective date recorded
- [ ] Customer warned of impact
- [ ] Entitlements reduced correctly
- [ ] Invoice adjusted
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If premature reduction harmed customer, restore entitlements and adjust billing with Finance approval.

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
| **Previous SOP** | [SOP-BIL-002 — Upgrade](SOP-BIL-002-upgrade.md) |
| **Next SOP** | [SOP-BIL-004 — Renewal](SOP-BIL-004-renewal.md) |
| **Related SOPs** | [SOP-CS-007](../customer-success/SOP-CS-007-churn-prevention.md), [SOP-BIL-007](SOP-BIL-007-subscription-cancellation.md) |
| **Related Documents** | [Pricing Guide](../../sales/pricing-guide.md) |
| **Required Forms** | Downgrade request form |
| **Required Checklists** | Downgrade impact checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
