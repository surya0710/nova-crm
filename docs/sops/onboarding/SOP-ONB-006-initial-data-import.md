# SOP-ONB-006 — Initial Data Import

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ONB-006 |
| **Title** | Initial Data Import |
| **Version** | 1.1 |
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

- **In scope:** Agreed initial data load with validation logs and customer acceptance of imported counts.
- **Import Center (Release 1.1.2):** Centralized CSV/XLSX imports for CRM (leads, customers, opportunities), HRMS (employees + masters), Projects (projects, milestones, tasks), and Administration (users via invitation).
- **Out of scope:** Ongoing integrations and post-go-live migrations (Migrations folder SOPs when published).

See also: `docs/imports/README.md`, `docs/launch/data-migration-validation.md` (Program 15.8).

## Preconditions

- [ ] Pre-import backup completed (SOP-MNT-002)
- [ ] Import templates downloaded from **Administration → Import Center** and validated offline
- [ ] Customer written approval to import
- [ ] Queue worker available for large files (`php artisan queue:work`)

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Import Center | `imports.create` + module scope (`imports.crm` / `imports.hrms` / `imports.projects` / `imports.administration`) | Manager / HR / Owner as applicable |
| Backup storage | Implementation / DevOps | Pre-import backup |

## Step-by-step Procedure

### 1. Prepare

1. Confirm import scope against Order Form / kickoff.
2. Take backup (SOP-MNT-002).
3. Download current templates from Import Center; dry-run validate offline.
4. Import masters before transactional data (see `docs/imports/import-guides.md`).

### 2. Execute

1. Open **Administration → Import Center**.
2. For each entity: upload → map → preview → confirm → wait for completion.
3. Capture Import History counts and attach error reports if any.
4. For employees with login: verify invitations via Identity Platform (SOP-ONB-005).
5. Spot-check samples with Customer Admin.

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
| 1.1 | Operations | 2026-07-25 | Clarify Import Platform coverage (Leads/Customers); HRMS/Projects via UI/scripts (15.8) | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
