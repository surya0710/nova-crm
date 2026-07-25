# SOP-REL-004 — Rollback

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-REL-004 |
| **Title** | Rollback |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Release Management |
| **Owner** | DevOps / Release Manager |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Revert a failed production release to the last known good state.

## Scope

- **In scope:** Application rollback, optional DB restore approval, and customer communication.
- **Out of scope:** Forward fixes when rollback is riskier than hotfix (Tech Lead decision).

## Preconditions

- [ ] Rollback decision by Release Manager / Tech Lead
- [ ] Prior artifact available

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Production | DevOps | Redeploy prior artifact |

## Step-by-step Procedure

### 1. Rollback

1. `php artisan down` if needed.
2. Redeploy previous release artifact.
3. Restore DB only if migration irreversible and approved.
4. `php artisan up`.
5. RCA within 48 hours.

Also see [deployment rollback](../../deployment/rollback.md).

## Validation Checklist

- [ ] Prior version serving
- [ ] Health green
- [ ] Customers updated
- [ ] RCA scheduled
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If rollback fails, escalate disaster recovery / incident commander immediately.

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
| **Previous SOP** | [SOP-REL-003 — Production Deployment](SOP-REL-003-production-deployment.md) |
| **Next SOP** | [SOP-REL-005 — Post-release Validation](SOP-REL-005-post-release-validation.md) |
| **Related SOPs** | [SOP-MNT-003](../maintenance/SOP-MNT-003-restore.md), [SOP-SUP-002](../support/SOP-SUP-002-incident-response.md) |
| **Related Documents** | [Rollback guide](../../deployment/rollback.md) |
| **Required Forms** | Rollback decision record |
| **Required Checklists** | Rollback execution checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
