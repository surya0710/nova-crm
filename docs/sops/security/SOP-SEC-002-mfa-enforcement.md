# SOP-SEC-002 — MFA Enforcement

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SEC-002 |
| **Title** | MFA Enforcement |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Security |
| **Owner** | Security Lead |
| **Reviewer** | Operations Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Require multi-factor authentication for privileged and policy-covered users.

## Scope

- **In scope:** MFA enrollment, enforcement flags, and exception handling.
- **Out of scope:** Credential rotation (SOP-SEC-003) and lockouts (SOP-SEC-006).

## Preconditions

- [ ] MFA method supported by deployment
- [ ] User identity verified

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Auth / MFA settings | Security / Platform Admin | Enforce MFA |

## Step-by-step Procedure

### 1. Enforce

1. Enable MFA for platform admins and contracted customer privileged roles.
2. Verify enrollment before granting elevated access.
3. Document any time-boxed exceptions with expiry.

## Validation Checklist

- [ ] Covered users enrolled
- [ ] Exceptions time-boxed and approved
- [ ] Spot-check login requires second factor
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If MFA misconfigured blocking all logins, use break-glass per SOP-ADM-001 exception; fix config; revoke break-glass within 24h.

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
| **Previous SOP** | [SOP-SEC-001 — Platform Administrator Access](SOP-SEC-001-platform-administrator-access.md) |
| **Next SOP** | [SOP-SEC-003 — Credential Rotation](SOP-SEC-003-credential-rotation.md) |
| **Related SOPs** | [SOP-ONB-005](../onboarding/SOP-ONB-005-user-provisioning.md), [SOP-SEC-006](SOP-SEC-006-user-lockout.md) |
| **Related Documents** | [Sales security overview](../../sales/security-overview.md) |
| **Required Forms** | MFA exception request |
| **Required Checklists** | MFA enrollment checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
