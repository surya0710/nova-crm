# SOP-OFF-006 — Permanent Deletion

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-OFF-006 |
| **Title** | Permanent Deletion |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Offboarding |
| **Owner** | DevOps / Ops |
| **Reviewer** | Operations Lead / Legal |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Permanently delete customer data after retention expiry with dual approval.

## Scope

- **In scope:** Irreversible deletion of org data and backups per policy, with audit evidence.
- **Out of scope:** Disable-only states and exports.

## Preconditions

- [ ] Retention expired
- [ ] No legal hold
- [ ] Dual approval (Ops Lead + Legal/Finance as required)

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Database / storage / backups | DevOps | Delete per runbook |

## Step-by-step Procedure

### 1. Delete with approvals

1. Confirm dual approval on ticket.
2. Delete org data and eligible backups per runbook.
3. Record certificates/evidence of deletion.
4. Close offboarding ticket.

## Validation Checklist

- [ ] Dual approval recorded
- [ ] Data deleted per runbook
- [ ] Evidence attached
- [ ] Offboarding ticket closed
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Deletion is irreversible — if wrong org targeted, stop immediately, escalate Security/Legal, and follow incident process. Prevention relies on dual approval and org ID verification.

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
| **Previous SOP** | [SOP-OFF-005 — Data Retention](SOP-OFF-005-data-retention.md) |
| **Next SOP** | None (lifecycle end) |
| **Related SOPs** | [SOP-OFF-001](SOP-OFF-001-subscription-closure.md), [SOP-SEC-004](../security/SOP-SEC-004-security-incident.md) |
| **Related Documents** | Retention policy |
| **Required Forms** | Deletion approval form |
| **Required Checklists** | Permanent deletion checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
