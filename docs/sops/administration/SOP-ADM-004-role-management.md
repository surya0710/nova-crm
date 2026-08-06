# SOP-ADM-004 — Role Management

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ADM-004 |
| **Title** | Role Management |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Administration |
| **Owner** | Implementation Lead / Org Admin |
| **Reviewer** | Security Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Define and maintain organization roles that map job functions to permission sets.

## Scope

- **In scope:** Creating, renaming, cloning, and retiring org roles.
- **Out of scope:** Granting individual permissions (SOP-ADM-005) and user assignment (SOP-ONB-005).

## Preconditions

- [ ] Role map approved by Customer Admin
- [ ] Permission naming standards reviewed

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Organization Roles | Org Admin / Implementation | Manage roles |

## Step-by-step Procedure

### 1. Design

1. Map job functions to roles using least privilege.
2. Prefer templates from [permission templates](../../developer/permission-templates.md) when available.

### 2. Implement

1. Create or update roles.
2. Document role purpose on the onboarding/security ticket.
3. Assign users only after Customer Admin sign-off.

## Validation Checklist

- [ ] Role map signed off
- [ ] No wildcard admin roles for standard users
- [ ] Roles documented on ticket
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Disable erroneous roles; reassign users to last known good roles.

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
| **Previous SOP** | [SOP-ADM-003 — Subscription Assignment](SOP-ADM-003-subscription-assignment.md) |
| **Next SOP** | [SOP-ADM-005 — Permission Management](SOP-ADM-005-permission-management.md) |
| **Related SOPs** | [SOP-ADM-005](SOP-ADM-005-permission-management.md), [SOP-SEC-005](../security/SOP-SEC-005-permission-audit.md) |
| **Related Documents** | [Permission naming standards](../../developer/permission-naming-standards.md), [Role hierarchy](../../developer/role-hierarchy.md) |
| **Required Forms** | Role map sign-off |
| **Required Checklists** | Least-privilege role design checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
