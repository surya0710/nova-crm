# SOP-ADM-008 — Notifications

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ADM-008 |
| **Title** | Notifications |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Administration |
| **Owner** | Org Admin / Implementation |
| **Reviewer** | Customer Success |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Configure notification defaults so users receive operational alerts without noise.

## Scope

- **In scope:** Workspace notification defaults and channel settings documented for the org.
- **Out of scope:** Platform-wide mail subsystem incidents (Support / Maintenance).

## Preconditions

- [ ] Mail / notification channels healthy
- [ ] Customer preference on alert volume known

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Notification settings | Org Admin | Configure defaults |

## Step-by-step Procedure

### 1. Configure

1. Review [workspace notifications](../../product/workspace-notifications.md).
2. Enable required operational notifications; disable noisy non-essential defaults per customer preference.
3. Send a test notification to Customer Admin.

## Validation Checklist

- [ ] Test notification received
- [ ] Defaults documented on onboarding ticket
- [ ] Customer Admin accepts noise level
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Restore previous notification defaults; disable failing channel and escalate to Support if delivery broken.

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
| **Previous SOP** | [SOP-ADM-007 — Branding](SOP-ADM-007-branding.md) |
| **Next SOP** | [SOP-ONB-005 — User Provisioning](../onboarding/SOP-ONB-005-user-provisioning.md) |
| **Related SOPs** | [SOP-ONB-004](../onboarding/SOP-ONB-004-organization-configuration.md), [SOP-SUP-005](../support/SOP-SUP-005-customer-communication.md) |
| **Related Documents** | [Workspace notifications](../../product/workspace-notifications.md) |
| **Required Forms** | Notification preference worksheet |
| **Required Checklists** | Test notification checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
