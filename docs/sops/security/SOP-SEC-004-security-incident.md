# SOP-SEC-004 — Security Incident

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SEC-004 |
| **Title** | Security Incident |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Security |
| **Owner** | Security Lead / On-call |
| **Reviewer** | Operations Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Respond to suspected or confirmed security incidents with containment, investigation, and notification.

## Scope

- **In scope:** Security incident declaration, containment, forensics coordination, and customer/legal notification triggers.
- **Out of scope:** Non-security production incidents (SOP-SUP-002) unless overlap.

## Preconditions

- [ ] Suspected compromise, data exposure, or abuse report

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Logs / IAM / hosts | Security / On-call | Contain and investigate |

## Step-by-step Procedure

### 1. Contain

1. Disable compromised accounts (SOP-SEC-006); rotate secrets (SOP-SEC-003).
2. Preserve logs; do not wipe evidence.
3. Engage incident commander; follow [Incident Response Plan](../../operations/incident-response-plan.md) security addendum if present.

### 2. Notify and remediate

1. Determine notification duties (customers, authorities) with Legal.
2. Remediate root cause; schedule post-incident review.

## Validation Checklist

- [ ] Containment actions logged
- [ ] Evidence preserved
- [ ] Legal/CS notified as required
- [ ] Post-incident review scheduled
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Do not silently rollback containment without Security Lead approval.

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
| **Previous SOP** | [SOP-SEC-003 — Credential Rotation](SOP-SEC-003-credential-rotation.md) |
| **Next SOP** | [SOP-SEC-005 — Permission Audit](SOP-SEC-005-permission-audit.md) |
| **Related SOPs** | [SOP-SUP-002](../support/SOP-SUP-002-incident-response.md), [SOP-SEC-006](SOP-SEC-006-user-lockout.md) |
| **Related Documents** | [Incident Response Plan](../../operations/incident-response-plan.md) |
| **Required Forms** | Security incident record |
| **Required Checklists** | Containment checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
