# SOP-ONB-006 — Initial Data Import

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ONB-006 |
| **Title** | Initial Data Import |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Onboarding |
| **Owner** | Implementation Lead |
| **Reviewer** | Customer Admin |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Import agreed historical or seed data with validation so go-live starts with trusted records.

## Scope

- **In scope:** Scoped CRM/HR/project imports, validation logs, and customer acceptance of imported counts.
- **Out of scope:** Ongoing integrations and post-go-live migrations (Migrations folder SOPs when published).

## Preconditions

- [ ] Pre-import backup completed (SOP-MNT-002)
- [ ] Import templates populated and validated offline
- [ ] Customer written approval to import

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Import tools / documented commands | Implementation | Run import in maintenance window if required |
| Backup storage | Implementation / DevOps | Pre-import backup |

## Step-by-step Procedure

### 1. Prepare

1. Confirm import scope against Order Form / kickoff.
2. Take backup (SOP-MNT-002).
3. Dry-run or validate CSV/templates for required fields.

### 2. Execute

1. Import in agreed order (typically reference data → masters → transactions).
2. Capture import logs and row counts.
3. Spot-check samples with Customer Admin.

### 3. Accept

1. Customer signs import acceptance on the onboarding ticket.

## Validation Checklist

- [ ] Pre-import backup stored
- [ ] Import logs attached
- [ ] Counts match expected within agreed tolerance
- [ ] Customer acceptance recorded
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Stop import; restore from pre-import backup via SOP-MNT-003; do not partial-commit without Implementation Lead approval.

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
| **Previous SOP** | [SOP-ONB-005 — User Provisioning](SOP-ONB-005-user-provisioning.md) |
| **Next SOP** | [SOP-ONB-007 — Go-Live Checklist](SOP-ONB-007-go-live-checklist.md) |
| **Related SOPs** | [SOP-MNT-002](../maintenance/SOP-MNT-002-backup.md), [SOP-MNT-003](../maintenance/SOP-MNT-003-restore.md) |
| **Related Documents** | [Onboarding Playbook](../../onboarding/playbook.md) |
| **Required Forms** | Import acceptance sign-off |
| **Required Checklists** | Import order and validation checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
