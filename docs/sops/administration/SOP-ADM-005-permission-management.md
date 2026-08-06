# SOP-ADM-005 — Permission Management

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ADM-005 |
| **Title** | Permission Management |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Administration |
| **Owner** | Implementation Lead / Org Admin |
| **Reviewer** | Security Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Grant and revoke permissions on roles so access remains least-privilege and auditable.

## Scope

- **In scope:** Permission assignment on roles and periodic review triggers.
- **Out of scope:** Security incident response and user lockout.

## Preconditions

- [ ] Roles exist (SOP-ADM-004)
- [ ] Change request describing permission deltas

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Organization Permissions | Org Admin / Implementation | Edit role permissions |

## Step-by-step Procedure

### 1. Change permissions

1. Identify role and required permission names.
2. Apply grants/revokes; avoid broad admin packs unless justified.
3. Test with a non-production user when available.

### 2. Record

1. List before/after permissions on the ticket.
2. Schedule permission audit if high-risk (SOP-SEC-005).

## Validation Checklist

- [ ] Permission deltas match request
- [ ] Test user validates access
- [ ] Ticket documents before/after
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Restore prior permission set from ticket snapshot; notify Security if over-grant occurred.

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
| **Previous SOP** | [SOP-ADM-004 — Role Management](SOP-ADM-004-role-management.md) |
| **Next SOP** | [SOP-ADM-006 — Workspace Configuration](SOP-ADM-006-workspace-configuration.md) |
| **Related SOPs** | [SOP-ADM-004](SOP-ADM-004-role-management.md), [SOP-SEC-005](../security/SOP-SEC-005-permission-audit.md) |
| **Related Documents** | [Dynamic RBAC architecture](../../developer/dynamic-rbac-architecture.md) |
| **Required Forms** | Permission change request |
| **Required Checklists** | High-risk permission review checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
