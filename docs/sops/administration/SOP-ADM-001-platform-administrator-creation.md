# SOP-ADM-001 — Platform Administrator Creation

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ADM-001 |
| **Title** | Platform Administrator Creation |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Administration |
| **Owner** | Platform Operator |
| **Reviewer** | Security Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Create platform-level administrators with least privilege and documented approval.

## Scope

- **In scope:** Platform admin account creation, role assignment, and access logging.
- **Out of scope:** Customer org admins (SOP-ONB-005) and MFA enforcement policy (SOP-SEC-002).

## Preconditions

- [ ] Written approval from Operations Lead or Security Lead
- [ ] Named individual and business justification

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Platform user admin | Existing Platform Admin | Create peer admin |

## Step-by-step Procedure

### 1. Approve and create

1. Record approval on a change/security ticket.
2. Create platform admin with least-privilege platform role.
3. Force password reset / invite flow; enable MFA (SOP-SEC-002).

### 2. Verify

1. Confirm login to `/platform`.
2. Confirm audit log entry for account creation.

## Validation Checklist

- [ ] Approval ticket linked
- [ ] MFA enabled
- [ ] Audit log entry present
- [ ] Access limited to required platform roles
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Disable the platform admin account immediately; rotate any shared secrets if exposed; notify Security (SOP-SEC-004 if incident).

## Exceptions

| Exception | Handling | Approver |
|-----------|----------|----------|
| Break-glass emergency admin | Time-boxed account; revoke within 24h | Security Lead |

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
| **Previous SOP** | [SOP-SEC-001 — Platform Administrator Access](../security/SOP-SEC-001-platform-administrator-access.md) |
| **Next SOP** | [SOP-ADM-002 — Organization Administration](SOP-ADM-002-organization-administration.md) |
| **Related SOPs** | [SOP-SEC-001](../security/SOP-SEC-001-platform-administrator-access.md), [SOP-SEC-002](../security/SOP-SEC-002-mfa-enforcement.md) |
| **Related Documents** | [Platform Admin Guide](../../onboarding/platform-admin-guide.md) |
| **Required Forms** | Platform admin access request |
| **Required Checklists** | Least-privilege + MFA checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
