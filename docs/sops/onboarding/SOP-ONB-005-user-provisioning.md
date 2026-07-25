# SOP-ONB-005 — User Provisioning

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ONB-005 |
| **Title** | User Provisioning |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Onboarding |
| **Owner** | Implementation Lead |
| **Reviewer** | Customer Admin |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Create initial administrators and key users with correct roles so the customer can operate licensed modules securely.

## Scope

- **In scope:** Initial admin, key user invites, and role assignment for go-live cohort.
- **Out of scope:** Ongoing HR joiner/leaver processes after handover; permission template design (SOP-ADM-004/005).

## Preconditions

- [ ] Roles mapped and signed off
- [ ] User list for go-live cohort available
- [ ] Seat capacity confirmed

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Organization Users | Org Admin / Implementation | Invite users |
| Roles & permissions | Implementation | Assign roles |

## Step-by-step Procedure

### 1. Create admins

1. Confirm primary Customer Admin can access the org.
2. Create backup admin if contracted.

### 2. Provision cohort

1. Invite key users within seat limits.
2. Assign roles per signed role map (SOP-ADM-004).
3. Verify MFA expectations with Security (SOP-SEC-002) when enforced.

## Validation Checklist

- [ ] Primary admin login verified
- [ ] Key users invited within seat limits
- [ ] Roles match signed map
- [ ] No excess seats consumed
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Disable incorrectly provisioned users; reassign roles; reclaim seats.

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
| **Previous SOP** | [SOP-ONB-004 — Organization Configuration](SOP-ONB-004-organization-configuration.md) |
| **Next SOP** | [SOP-ONB-006 — Initial Data Import](SOP-ONB-006-initial-data-import.md) |
| **Related SOPs** | [SOP-ADM-004](../administration/SOP-ADM-004-role-management.md), [SOP-ADM-005](../administration/SOP-ADM-005-permission-management.md), [SOP-SEC-002](../security/SOP-SEC-002-mfa-enforcement.md) |
| **Related Documents** | [Org Admin Guide](../../onboarding/org-admin-guide.md) |
| **Required Forms** | User roster / role map |
| **Required Checklists** | User provisioning checklist (admins, cohort, roles, seats) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
