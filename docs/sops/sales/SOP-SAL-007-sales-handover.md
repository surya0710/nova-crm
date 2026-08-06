# SOP-SAL-007 — Sales Handover

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SAL-007 |
| **Title** | Sales Handover |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Sales |
| **Owner** | Account Executive |
| **Reviewer** | Customer Success Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Transfer a Closed Won customer to Implementation / CS with complete context so onboarding starts without tribal knowledge.

## Scope

- **In scope:** Handoff package, introductions, success metrics transfer, and kickoff scheduling.
- **Out of scope:** Organization provisioning and configuration (Onboarding SOPs).

## Preconditions

- [ ] Opportunity Closed Won with signed Order Form
- [ ] Discovery notes and success metrics available

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Onboarding / support ticketing | AE / CS | Create handoff ticket |
| CRM | AE | Attach artifacts |

## Step-by-step Procedure

### 1. Open handoff within 2 business days

- [ ] Create onboarding ticket with signed Order Form attached
- [ ] Introduce CS / Implementation owner to customer
- [ ] Transfer discovery notes and success metrics
- [ ] Confirm go-live target date
- [ ] Schedule kickoff

### 2. Complete handoff checklist

Use [Customer Handoff Checklist](../../onboarding/handoff-checklist.md).

## Validation Checklist

- [ ] Handoff ticket opened with Order Form
- [ ] CS / Implementation introduced
- [ ] Kickoff scheduled
- [ ] Handoff checklist complete
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If handoff was incomplete, reopen the onboarding ticket as blocked, notify CS Lead, and complete missing artifacts before provisioning.

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
| **Previous SOP** | [SOP-SAL-006 — Contract Execution](SOP-SAL-006-contract-execution.md) |
| **Next SOP** | [SOP-ONB-001 — Customer Onboarding](../onboarding/SOP-ONB-001-customer-onboarding.md) |
| **Related SOPs** | [SOP-ONB-001](../onboarding/SOP-ONB-001-customer-onboarding.md), [SOP-ONB-008](../onboarding/SOP-ONB-008-customer-handover.md), [SOP-CS-001](../customer-success/SOP-CS-001-welcome-process.md) |
| **Related Documents** | [Handoff Checklist](../../onboarding/handoff-checklist.md), [Onboarding Playbook](../../onboarding/playbook.md) |
| **Required Forms** | Signed Order Form, discovery notes export |
| **Required Checklists** | [Customer Handoff Checklist](../../onboarding/handoff-checklist.md) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
