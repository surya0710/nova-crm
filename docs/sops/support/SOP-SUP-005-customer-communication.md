# SOP-SUP-005 — Customer Communication

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SUP-005 |
| **Title** | Customer Communication |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Support |
| **Owner** | Support Agent / CS |
| **Reviewer** | Support Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Communicate clearly during tickets and incidents: acknowledge, diagnose, update, resolve, confirm.

## Scope

- **In scope:** Customer-facing updates, tone, cadence, and confirmation of resolution.
- **Out of scope:** Internal engineering chat and legal notices.

## Preconditions

- [ ] Ticket or incident open
- [ ] Customer contact known

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Support / email / status page | Support | Send updates |

## Step-by-step Procedure

### 1. Cadence

1. Acknowledge → diagnose → update → resolve → confirm.
2. P1 updates at least every 60 minutes until mitigated.
3. For releases/maintenance, follow [Release communication](../../support/release-communication.md).

## Validation Checklist

- [ ] Customer received timely updates
- [ ] Resolution confirmed or next update time set
- [ ] No confidential internal data leaked
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Send correction message if inaccurate info was shared; notify Support Lead.

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
| **Previous SOP** | [SOP-SUP-004 — Feature Requests](SOP-SUP-004-feature-requests.md) |
| **Next SOP** | [SOP-SUP-006 — SLA Management](SOP-SUP-006-sla-management.md) |
| **Related SOPs** | [SOP-SUP-002](SOP-SUP-002-incident-response.md), [SOP-REL-005](../release-management/SOP-REL-005-post-release-validation.md) |
| **Related Documents** | [Release communication](../../support/release-communication.md) |
| **Required Forms** | Customer update template |
| **Required Checklists** | P1 update cadence checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
