# SOP-OFF-001 — Subscription Closure

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-OFF-001 |
| **Title** | Subscription Closure |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Offboarding |
| **Owner** | Billing / Ops |
| **Reviewer** | Operations Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Close the commercial subscription and open the technical offboarding sequence.

## Scope

- **In scope:** Confirm cancellation effective date and initiate data export/disable chain.
- **Out of scope:** Detailed export/delete mechanics in later OFF SOPs.

## Preconditions

- [ ] SOP-BIL-007 completed or equivalent written cancellation
- [ ] Org ID confirmed

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Billing + offboarding ticket | Billing / Ops | Close and hand off |

## Step-by-step Procedure

### 1. Close and hand off

1. Confirm effective date and final billing state.
2. Open offboarding ticket with org ID, retention requirements, and contacts.
3. Proceed to SOP-OFF-002 Data Export before disable when contract requires export.

## Validation Checklist

- [ ] Offboarding ticket opened
- [ ] Effective date clear
- [ ] Export requirement captured
- [ ] CS/Support notified
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If closure was in error, reinstate via Billing and cancel offboarding ticket before disable/delete.

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
| **Previous SOP** | [SOP-BIL-007 — Subscription Cancellation](../billing/SOP-BIL-007-subscription-cancellation.md) |
| **Next SOP** | [SOP-OFF-002 — Data Export](SOP-OFF-002-data-export.md) |
| **Related SOPs** | [SOP-CS-007](../customer-success/SOP-CS-007-churn-prevention.md), [SOP-OFF-004](SOP-OFF-004-account-disable.md) |
| **Related Documents** | [Customer Success churn docs](../../customer-success/churn-prevention.md) |
| **Required Forms** | Cancellation / offboarding form |
| **Required Checklists** | Closure handoff checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
