# SOP-SEC-001 — Platform Administrator Access

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SEC-001 |
| **Title** | Platform Administrator Access |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Security |
| **Owner** | Security Lead |
| **Reviewer** | Operations Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Govern who may hold platform administrator privileges and how access is reviewed.

## Scope

- **In scope:** Access requests, approvals, periodic review, and revocation of platform admins.
- **Out of scope:** Creating the account technically (SOP-ADM-001) and org-level admins.

## Preconditions

- [ ] Access request with business justification
- [ ] Manager + Security approval path defined

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Platform admin roster | Security Lead | Approve/review |

## Step-by-step Procedure

### 1. Request and approve

1. Requester submits justification and duration.
2. Security Lead approves least-privilege role.
3. Execute creation via SOP-ADM-001.

### 2. Review

1. Quarterly review of platform admin roster.
2. Revoke leavers within 24 hours of HR notification.

## Validation Checklist

- [ ] Approval recorded
- [ ] Roster current
- [ ] Leavers revoked within 24h
- [ ] MFA enforced
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Immediately disable disputed accounts; force session logout; rotate credentials (SOP-SEC-003).

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
| **Previous SOP** | [SOP-ADM-001 — Platform Administrator Creation](../administration/SOP-ADM-001-platform-administrator-creation.md) |
| **Next SOP** | [SOP-SEC-002 — MFA Enforcement](SOP-SEC-002-mfa-enforcement.md) |
| **Related SOPs** | [SOP-SEC-005](SOP-SEC-005-permission-audit.md), [SOP-SEC-006](SOP-SEC-006-user-lockout.md) |
| **Related Documents** | [Platform Admin Guide](../../onboarding/platform-admin-guide.md) |
| **Required Forms** | Platform access request form |
| **Required Checklists** | Quarterly admin roster review |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
