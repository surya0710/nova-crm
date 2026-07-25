# SOP-ONB-003 — Module Licensing

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ONB-003 |
| **Title** | Module Licensing |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Onboarding |
| **Owner** | Platform Operator |
| **Reviewer** | Implementation Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Enable only the modules and seat counts purchased on the Order Form.

## Scope

- **In scope:** Plan/module assignment and seat limits.
- **Out of scope:** Subscription commercial changes after go-live (Billing SOPs).

## Preconditions

- [ ] Organization provisioned (SOP-ONB-002)
- [ ] Order Form module list and seat counts available

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Platform subscriptions / plans | Platform Operator | Assign plan and modules |

## Step-by-step Procedure

### 1. Assign plan and modules

1. Open the organization subscription panel.
2. Assign plan matching Order Form SKUs.
3. Enable purchased modules only; leave unpurchased modules disabled.

### 2. Set seats

1. Set seat / user limits to match Order Form.
2. Confirm billing period start date with Billing (SOP-BIL-001) when commercial activation is separate.

## Validation Checklist

- [ ] Enabled modules match Order Form
- [ ] Seat limits match Order Form
- [ ] Screenshot or export attached to onboarding ticket
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Disable incorrectly enabled modules; reduce seats to contracted amounts; notify AE if commercial mismatch found.

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
| **Previous SOP** | [SOP-ONB-002 — Organization Provisioning](SOP-ONB-002-organization-provisioning.md) |
| **Next SOP** | [SOP-ONB-004 — Organization Configuration](SOP-ONB-004-organization-configuration.md) |
| **Related SOPs** | [SOP-ADM-003](../administration/SOP-ADM-003-subscription-assignment.md), [SOP-BIL-001](../billing/SOP-BIL-001-new-subscription.md) |
| **Related Documents** | [Pricing Guide](../../sales/pricing-guide.md) |
| **Required Forms** | Order Form SKU schedule |
| **Required Checklists** | Module/seat verification checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
