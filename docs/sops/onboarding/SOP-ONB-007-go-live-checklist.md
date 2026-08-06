# SOP-ONB-007 — Go-Live Checklist

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ONB-007 |
| **Title** | Go-Live Checklist |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Onboarding |
| **Owner** | Implementation Lead |
| **Reviewer** | Customer Success |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Confirm readiness gates before declaring the customer live in production use.

## Scope

- **In scope:** UAT sign-off, go-live checklist execution, and cutover communication.
- **Out of scope:** Ongoing support after handover (Support / CS SOPs).

## Preconditions

- [ ] Configuration and imports complete
- [ ] UAT defects closed or waived in writing
- [ ] [Go-live Checklist](../../onboarding/go-live-checklist.md) assigned

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Customer org | Implementation + Customer Admin | Execute checklist |
| Monitoring | Ops | Watch health during cutover |

## Step-by-step Procedure

### 1. Execute checklist

Complete [Go-live Checklist](../../onboarding/go-live-checklist.md) item by item with Customer Admin.

### 2. Communicate

1. Confirm support channel and SLA.
2. Announce go-live to stakeholders.
3. Book CS welcome / training (SOP-CS-001 / SOP-CS-002).

## Validation Checklist

- [ ] Go-live checklist signed
- [ ] Support channel communicated
- [ ] CS schedule booked
- [ ] No open P1 blockers
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Delay go-live announcement; keep users on legacy process; open blocked ticket with remaining checklist gaps.

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
| **Previous SOP** | [SOP-ONB-006 — Initial Data Import](SOP-ONB-006-initial-data-import.md) |
| **Next SOP** | [SOP-ONB-008 — Customer Handover](SOP-ONB-008-customer-handover.md) |
| **Related SOPs** | [SOP-ONB-008](SOP-ONB-008-customer-handover.md), [SOP-CS-001](../customer-success/SOP-CS-001-welcome-process.md), [SOP-SUP-006](../support/SOP-SUP-006-sla-management.md) |
| **Related Documents** | [Go-live Checklist](../../onboarding/go-live-checklist.md) |
| **Required Forms** | UAT / go-live sign-off |
| **Required Checklists** | [Go-live Checklist](../../onboarding/go-live-checklist.md) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
