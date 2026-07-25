# SOP-ONB-008 — Customer Handover

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ONB-008 |
| **Title** | Customer Handover |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Onboarding |
| **Owner** | Implementation Lead |
| **Reviewer** | Customer Success Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Formally transfer ownership from Implementation to Customer Success and Support after go-live.

## Scope

- **In scope:** Handover meeting, artifact transfer, named CS owner assignment, and ticket closure.
- **Out of scope:** Quarterly reviews and renewals (Customer Success SOPs).

## Preconditions

- [ ] Go-live checklist signed (SOP-ONB-007)
- [ ] Named CS owner identified

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| CS tools / CRM | CS | Assume account ownership |
| Support | Support Lead | Confirm support path |

## Step-by-step Procedure

### 1. Handover meeting

1. Review success plan, open risks, and admin contacts.
2. Transfer configuration notes and import logs.
3. Confirm support intake path and SLA.

### 2. Close implementation

1. Assign CS owner on the account.
2. Close onboarding ticket with links to artifacts.
3. Schedule first health check (SOP-CS-003).

## Validation Checklist

- [ ] CS owner named on account
- [ ] Onboarding ticket closed with artifacts
- [ ] First health check scheduled
- [ ] Customer acknowledges handover
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If CS cannot accept, keep Implementation as interim owner; escalate to Operations Lead within 1 business day.

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
| **Previous SOP** | [SOP-ONB-007 — Go-Live Checklist](SOP-ONB-007-go-live-checklist.md) |
| **Next SOP** | [SOP-CS-001 — Welcome Process](../customer-success/SOP-CS-001-welcome-process.md) |
| **Related SOPs** | [SOP-CS-001](../customer-success/SOP-CS-001-welcome-process.md), [SOP-SUP-001](../support/SOP-SUP-001-ticket-handling.md) |
| **Related Documents** | [Customer Success README](../../customer-success/README.md) |
| **Required Forms** | Handover acceptance note |
| **Required Checklists** | Implementation → CS handover agenda |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
