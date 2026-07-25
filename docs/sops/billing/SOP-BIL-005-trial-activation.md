# SOP-BIL-005 — Trial Activation

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-BIL-005 |
| **Title** | Trial Activation |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Billing |
| **Owner** | Billing / Sales Ops |
| **Reviewer** | Sales Manager |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Activate time-boxed trials with clear scope, duration, and conversion owner.

## Scope

- **In scope:** Trial subscription creation, module limits, and expiry date setting.
- **Out of scope:** Paid conversion (SOP-BIL-001) and trial expiry handling (SOP-BIL-006).

## Preconditions

- [ ] Trial approved per pricing matrix
- [ ] Prospect org identity known

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Billing / Platform | Billing / Platform Operator | Create trial |

## Step-by-step Procedure

### 1. Activate

1. Create trial org/subscription with approved modules and end date.
2. Assign AE/CS conversion owner.
3. Send trial welcome with end date and success criteria.

## Validation Checklist

- [ ] Trial end date set
- [ ] Modules limited as approved
- [ ] Conversion owner assigned
- [ ] Customer notified of end date
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Disable trial early if abuse detected (SOP-SEC-*); notify AE.

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
| **Previous SOP** | [SOP-BIL-004 — Renewal](SOP-BIL-004-renewal.md) |
| **Next SOP** | [SOP-BIL-006 — Trial Expiry](SOP-BIL-006-trial-expiry.md) |
| **Related SOPs** | [SOP-SAL-005](../sales/SOP-SAL-005-pricing-approval.md), [SOP-ONB-002](../onboarding/SOP-ONB-002-organization-provisioning.md) |
| **Related Documents** | [Pricing Guide](../../sales/pricing-guide.md) |
| **Required Forms** | Trial approval note |
| **Required Checklists** | Trial activation checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
