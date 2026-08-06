# SOP-REL-001 — Release Preparation

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-REL-001 |
| **Title** | Release Preparation |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Release Management |
| **Owner** | Release Manager / Tech Lead |
| **Reviewer** | Operations Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Prepare a release candidate with scoped changes, notes, and QA readiness.

## Scope

- **In scope:** Scope freeze, changelog, test plan, and artifact readiness.
- **Out of scope:** Final approval (SOP-REL-002) and production deploy execution.

## Preconditions

- [ ] Candidate commits merged to release branch
- [ ] Known issues listed

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| VCS / CI | Release Manager | Tag candidate |
| QA | QA | Execute tests |

## Step-by-step Procedure

### 1. Prepare

1. Scope freeze for release candidate.
2. Draft changelog / release notes.
3. Attach QA plan from [Release checklist](../../operations/release-checklist.md).
4. Ensure docs updated for customer-facing changes.

## Validation Checklist

- [ ] Scope frozen
- [ ] Release notes drafted
- [ ] CI green on candidate
- [ ] QA plan attached
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If critical defect found, unfreeze only with Release Manager approval; regenerate notes.

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
| **Previous SOP** | [SOP-MON-005 — Performance Review](../monitoring/SOP-MON-005-performance-review.md) |
| **Next SOP** | [SOP-REL-002 — Release Approval](SOP-REL-002-release-approval.md) |
| **Related SOPs** | [SOP-SUP-003](../support/SOP-SUP-003-bug-escalation.md), [SOP-MNT-001](../maintenance/SOP-MNT-001-application-upgrade.md) |
| **Related Documents** | [Release checklist](../../operations/release-checklist.md), [Internal operations (legacy)](../internal-operations.md) |
| **Required Forms** | Release candidate record |
| **Required Checklists** | [Release checklist](../../operations/release-checklist.md) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
