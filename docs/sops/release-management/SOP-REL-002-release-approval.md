# SOP-REL-002 — Release Approval

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-REL-002 |
| **Title** | Release Approval |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Release Management |
| **Owner** | Operations Lead |
| **Reviewer** | Tech Lead / Product |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Authorize production release based on QA sign-off and risk assessment.

## Scope

- **In scope:** Go/no-go decision, approver signatures, and maintenance window confirmation.
- **Out of scope:** Deploy execution (SOP-REL-003 / SOP-DEP-002).

## Preconditions

- [ ] SOP-REL-001 complete
- [ ] QA sign-off available
- [ ] Rollback plan identified

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Release ticket | Approvers | Record go/no-go |

## Step-by-step Procedure

### 1. Go / no-go

1. Review QA results, risk, and comms plan.
2. Obtain required approvals (Tech Lead; Product if customer-impacting; Security if sensitive).
3. Confirm deploy window and rollback owner (SOP-REL-004).

## Validation Checklist

- [ ] Approvals recorded
- [ ] Window confirmed
- [ ] Rollback owner named
- [ ] Support briefed
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If approval withdrawn, cancel window and notify stakeholders; do not deploy.

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
| **Previous SOP** | [SOP-REL-001 — Release Preparation](SOP-REL-001-release-preparation.md) |
| **Next SOP** | [SOP-REL-003 — Production Deployment](SOP-REL-003-production-deployment.md) |
| **Related SOPs** | [SOP-DEP-002](../deployment/SOP-DEP-002-production-deployment.md), [SOP-SUP-005](../support/SOP-SUP-005-customer-communication.md) |
| **Related Documents** | [Release checklist](../../operations/release-checklist.md) |
| **Required Forms** | Release approval form |
| **Required Checklists** | Go/no-go checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
