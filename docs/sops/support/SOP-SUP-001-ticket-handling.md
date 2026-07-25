# SOP-SUP-001 — Ticket Handling

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SUP-001 |
| **Title** | Ticket Handling |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Support |
| **Owner** | Support Agent (L1) |
| **Reviewer** | Support Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Acknowledge, classify, resolve, or escalate customer tickets consistently within SLA.

## Scope

- **In scope:** Ticket intake, classification, reproduction, resolution documentation, and closure.
- **Out of scope:** Major incident command (SOP-SUP-002) and feature request product routing (SOP-SUP-004).

## Preconditions

- [ ] Support console access
- [ ] SLA matrix available
- [ ] Org context identifiable

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Support ticketing | L1 Support | Own tickets |
| Customer org (read) | Support | Reproduce with least privilege |

## Step-by-step Procedure

### 1. Acknowledge and classify

1. Acknowledge within SLA response time ([SLA Matrix](../../support/sla-matrix.md)).
2. Classify: How-to, Defect, Incident, Feature request, Account/billing.

### 2. Reproduce and resolve

1. Reproduce with org context (use secure channel for credentials).
2. Resolve or escalate; document resolution.
3. Confirm customer acceptance before close.

Detailed handbook: [Support README](../../support/README.md) · [Ticket handling](../../support/ticket-handling.md).

## Validation Checklist

- [ ] Acknowledged within SLA
- [ ] Classification set
- [ ] Resolution or escalation documented
- [ ] Customer confirmation before close (or parked with owner/due date)
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If closed incorrectly, reopen ticket, restore prior status, and notify customer with apology and next update time.

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
| **Previous SOP** | [SOP-ONB-008 — Customer Handover](../onboarding/SOP-ONB-008-customer-handover.md) |
| **Next SOP** | [SOP-SUP-002 — Incident Response](SOP-SUP-002-incident-response.md) |
| **Related SOPs** | [SOP-SUP-003](SOP-SUP-003-bug-escalation.md), [SOP-SUP-005](SOP-SUP-005-customer-communication.md), [SOP-SUP-006](SOP-SUP-006-sla-management.md) |
| **Related Documents** | [Support Handbook](../../support/README.md), [Ticket handling](../../support/ticket-handling.md) |
| **Required Forms** | Support ticket form |
| **Required Checklists** | Ticket quality checklist (repro, impact, env, org ID) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
