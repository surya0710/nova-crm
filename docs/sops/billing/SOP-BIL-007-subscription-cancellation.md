# SOP-BIL-007 — Subscription Cancellation

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-BIL-007 |
| **Title** | Subscription Cancellation |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Billing |
| **Owner** | Billing Owner |
| **Reviewer** | Finance / CS |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Cancel a paid subscription commercially and trigger the offboarding chain.

## Scope

- **In scope:** Cancellation effective date, final invoice/credit, and handoff to Offboarding SOPs.
- **Out of scope:** Technical data deletion (owned by Offboarding).

## Preconditions

- [ ] Written cancellation request or non-renewal confirmed
- [ ] CS churn review completed or waived

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Billing | Billing | Cancel subscription |
| Offboarding ticket | Ops | Open SOP-OFF-001 |

## Step-by-step Procedure

### 1. Commercial cancel

1. Set cancellation effective date per contract.
2. Issue final invoice or credit per Finance.
3. Stop future renewals.

### 2. Hand off

1. Open offboarding ticket → SOP-OFF-001.
2. Notify Support to watch for access questions.

## Validation Checklist

- [ ] Cancellation effective date set
- [ ] Final billing settled or scheduled
- [ ] Offboarding ticket opened
- [ ] CS/Support notified
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If cancelled in error, reinstate subscription with Finance approval and halt offboarding.

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
| **Previous SOP** | [SOP-BIL-006 — Trial Expiry](SOP-BIL-006-trial-expiry.md) |
| **Next SOP** | [SOP-OFF-001 — Subscription Closure](../offboarding/SOP-OFF-001-subscription-closure.md) |
| **Related SOPs** | [SOP-CS-007](../customer-success/SOP-CS-007-churn-prevention.md), [SOP-OFF-001](../offboarding/SOP-OFF-001-subscription-closure.md) |
| **Related Documents** | [Renewal / churn docs](../../customer-success/churn-prevention.md) |
| **Required Forms** | Cancellation request form |
| **Required Checklists** | Cancellation handoff checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
