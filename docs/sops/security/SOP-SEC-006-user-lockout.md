# SOP-SEC-006 — User Lockout

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SEC-006 |
| **Title** | User Lockout |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Security |
| **Owner** | Support / Security / Org Admin |
| **Reviewer** | Security Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Lock user accounts promptly to stop unauthorized access or abusive behavior.

## Scope

- **In scope:** Disable/lock actions for platform and organization users, including emergency lockouts.
- **Out of scope:** Permanent offboarding deletion (SOP-OFF-*).

## Preconditions

- [ ] Identity of user confirmed
- [ ] Lockout reason recorded

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| User admin | Org Admin / Platform / Security | Disable user |

## Step-by-step Procedure

### 1. Lock

1. Disable/lock the user in the correct tenant or platform scope.
2. Invalidate sessions if supported.
3. Notify requester and Security when lockout is security-related.

### 2. Follow-up

1. For security cases, continue SOP-SEC-004.
2. For HR/offboarding, continue SOP-OFF-004.

## Validation Checklist

- [ ] User disabled
- [ ] Reason recorded
- [ ] Sessions invalidated when possible
- [ ] Follow-up SOP linked
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If wrong user locked, unlock immediately after identity verification; document error.

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
| **Previous SOP** | [SOP-SEC-005 — Permission Audit](SOP-SEC-005-permission-audit.md) |
| **Next SOP** | [SOP-OFF-004 — Account Disable](../offboarding/SOP-OFF-004-account-disable.md) |
| **Related SOPs** | [SOP-SEC-004](SOP-SEC-004-security-incident.md), [SOP-SUP-001](../support/SOP-SUP-001-ticket-handling.md) |
| **Related Documents** | [Org Admin Guide](../../onboarding/org-admin-guide.md) |
| **Required Forms** | Lockout request form |
| **Required Checklists** | Emergency lockout checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
