# SOP-REL-003 — Production Deployment

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-REL-003 |
| **Title** | Production Deployment |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Release Management |
| **Owner** | DevOps |
| **Reviewer** | Release Manager |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Execute the approved production deployment for a release train.

## Scope

- **In scope:** Governed production deploy referencing technical SOP-DEP-002 under release control.
- **Out of scope:** Technical one-off hotfixes without release train (still require change ticket).

## Preconditions

- [ ] SOP-REL-002 approved
- [ ] Backup complete

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Production | DevOps | Deploy |

## Step-by-step Procedure

### 1. Execute

1. Follow SOP-DEP-002 and [Production Deployment Checklist](../../operations/production-deployment-checklist.md).
2. Keep release bridge open until smoke passes.
3. Hand off to SOP-REL-005 for post-release validation.

## Validation Checklist

- [ ] Deploy completed per checklist
- [ ] Smoke passed
- [ ] Release ticket updated
- [ ] Monitoring watch started
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Invoke SOP-REL-004 immediately on failed smoke or P1 regression.

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
| **Previous SOP** | [SOP-REL-002 — Release Approval](SOP-REL-002-release-approval.md) |
| **Next SOP** | [SOP-REL-004 — Rollback](SOP-REL-004-rollback.md) |
| **Related SOPs** | [SOP-DEP-002](../deployment/SOP-DEP-002-production-deployment.md), [SOP-REL-005](SOP-REL-005-post-release-validation.md) |
| **Related Documents** | [Production Deployment Checklist](../../operations/production-deployment-checklist.md) |
| **Required Forms** | Release deploy ticket |
| **Required Checklists** | [Production Deployment Checklist](../../operations/production-deployment-checklist.md) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
