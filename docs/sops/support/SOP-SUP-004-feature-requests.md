# SOP-SUP-004 — Feature Requests

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SUP-004 |
| **Title** | Feature Requests |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Support |
| **Owner** | Support / CS |
| **Reviewer** | Product |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Capture customer feature requests with impact context and route them to Product without promising dates.

## Scope

- **In scope:** Feature request logging, impact assessment, and Product handoff.
- **Out of scope:** Defect fixes and committed roadmap delivery.

## Preconditions

- [ ] Customer request articulated
- [ ] Workaround assessed

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Product backlog / CRM | Support / CS | Log request |

## Step-by-step Procedure

### 1. Capture

1. Log impact, workaround, module, and pilot willingness.
2. Route to Product — do not promise dates in support chat.

### 2. Close loop

1. Inform customer that Product owns prioritization.
2. Link request ID on the support ticket.

## Validation Checklist

- [ ] Request logged with impact fields
- [ ] No unauthorized date commitments
- [ ] Product owner acknowledged
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If a date was incorrectly promised, retract immediately, notify Sales/CS Lead, and correct customer expectations in writing.

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
| **Previous SOP** | [SOP-SUP-003 — Bug Escalation](SOP-SUP-003-bug-escalation.md) |
| **Next SOP** | [SOP-SUP-005 — Customer Communication](SOP-SUP-005-customer-communication.md) |
| **Related SOPs** | [SOP-CS-006](../customer-success/SOP-CS-006-expansion-opportunity.md), [SOP-SAL-003](../sales/SOP-SAL-003-product-demonstration.md) |
| **Related Documents** | [Support README](../../support/README.md) |
| **Required Forms** | Feature request form |
| **Required Checklists** | Impact fields checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
