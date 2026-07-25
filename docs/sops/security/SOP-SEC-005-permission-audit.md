# SOP-SEC-005 — Permission Audit

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SEC-005 |
| **Title** | Permission Audit |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Security |
| **Owner** | Security Lead |
| **Reviewer** | Operations Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Periodically audit roles and permissions for least privilege and toxic combinations.

## Scope

- **In scope:** Scheduled and triggered permission audits for platform and sample tenant roles.
- **Out of scope:** Day-to-day permission changes (SOP-ADM-005).

## Preconditions

- [ ] Audit scope defined (platform / org list)
- [ ] Export of roles/permissions available

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| RBAC admin / exports | Security | Review grants |

## Step-by-step Procedure

### 1. Audit

1. Export roles and permissions for in-scope orgs/platform.
2. Flag admin sprawl, unused elevated roles, and toxic combos.
3. Open remediation tickets; track to closure.

## Validation Checklist

- [ ] Audit report attached
- [ ] Findings ticketed
- [ ] High-risk findings remediated or accepted with expiry
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If audit tooling fails, perform manual sampling of high-risk roles and reschedule full audit.

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
| **Previous SOP** | [SOP-SEC-004 — Security Incident](SOP-SEC-004-security-incident.md) |
| **Next SOP** | [SOP-SEC-006 — User Lockout](SOP-SEC-006-user-lockout.md) |
| **Related SOPs** | [SOP-ADM-004](../administration/SOP-ADM-004-role-management.md), [SOP-ADM-005](../administration/SOP-ADM-005-permission-management.md) |
| **Related Documents** | [Permission naming standards](../../developer/permission-naming-standards.md) |
| **Required Forms** | Permission audit report template |
| **Required Checklists** | Least-privilege findings checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
