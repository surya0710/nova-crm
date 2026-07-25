# SOP-ONB-001 — Customer Onboarding

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ONB-001 |
| **Title** | Customer Onboarding |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Onboarding |
| **Owner** | Implementation Lead |
| **Reviewer** | Operations Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Deliver a repeatable onboarding lifecycle from organization provisioning through go-live validation.

## Scope

- **In scope:** End-to-end onboarding orchestration across provisioning, configuration, import, UAT, and go-live.
- **Out of scope:** Post-go-live adoption (Customer Success) and production infra deploy (Deployment).

## Preconditions

- [ ] Signed Order Form / Closed Won deal
- [ ] Sales handoff package complete (SOP-SAL-007)
- [ ] Access to Platform console (`/platform`)

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Platform console | Platform Operator / Implementation | Org and subscription management |
| Customer org admin | Implementation + Customer Admin | Configuration |

## Step-by-step Procedure

### Procedure overview

| Step | Owner | SOP / Artifact |
|------|-------|----------------|
| 1. Organization provisioning | Platform Operator | SOP-ONB-002 |
| 2. Module licensing / subscription | Platform Operator | SOP-ONB-003 / SOP-ADM-003 |
| 3. Organization configuration | Implementation | SOP-ONB-004 |
| 4. User provisioning | Implementation + Admin | SOP-ONB-005 |
| 5. Initial data import | Implementation | SOP-ONB-006 |
| 6. Validation / UAT | Implementation + Customer | Sign-off |
| 7. Go-live | Implementation + CS | SOP-ONB-007 |
| 8. Customer handover | Implementation + CS | SOP-ONB-008 |

Follow detailed checklists in [Onboarding Playbook](../../onboarding/playbook.md).

## Validation Checklist

- [ ] Customer Admin can log in and perform core workflows
- [ ] Go-live checklist signed (SOP-ONB-007)
- [ ] Support channel and SLA communicated
- [ ] CS welcome / training schedule booked
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If go-live must be aborted, keep org in staging mode, disable production cutover communications, restore from pre-import backup if data is corrupt (SOP-MNT-003), and reschedule with CS.

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
| **Previous SOP** | [SOP-SAL-007 — Sales Handover](../sales/SOP-SAL-007-sales-handover.md) |
| **Next SOP** | [SOP-ONB-002 — Organization Provisioning](SOP-ONB-002-organization-provisioning.md) |
| **Related SOPs** | [SOP-ONB-002](SOP-ONB-002-organization-provisioning.md) through [SOP-ONB-008](SOP-ONB-008-customer-handover.md), [SOP-CS-001](../customer-success/SOP-CS-001-welcome-process.md) |
| **Related Documents** | [Onboarding Playbook](../../onboarding/playbook.md), [Go-live Checklist](../../onboarding/go-live-checklist.md) |
| **Required Forms** | Order Form, success plan draft |
| **Required Checklists** | [Go-live Checklist](../../onboarding/go-live-checklist.md), [Handoff Checklist](../../onboarding/handoff-checklist.md) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
