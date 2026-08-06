# SOP-ONB-004 — Organization Configuration

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ONB-004 |
| **Title** | Organization Configuration |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Onboarding |
| **Owner** | Implementation Lead |
| **Reviewer** | Customer Admin |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Configure org structure and workspace defaults so day-one workflows match the customer's operating model.

## Scope

- **In scope:** Branches, departments, calendars, CRM/Projects/HRMS baselines, branding handoff, and notifications defaults.
- **Out of scope:** User creation (SOP-ONB-005) and bulk data import (SOP-ONB-006).

## Preconditions

- [ ] Modules licensed (SOP-ONB-003)
- [ ] Org structure worksheet from kickoff

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Customer organization settings | Implementation / Customer Admin | Configure structure |

## Step-by-step Procedure

### 1. Structure

1. Configure branches to match org structure.
2. Configure department tree.
3. Configure holiday calendars and leave types when HRMS is in scope.

### 2. Workspace baselines

1. CRM: pipelines, sources, products as scoped.
2. Projects: types, statuses, defaults as scoped.
3. HRMS: shifts, leave, payroll basics as scoped.
4. Branding and notifications: follow SOP-ADM-007 and SOP-ADM-008 with Customer Admin.

## Validation Checklist

- [ ] Structure matches kickoff worksheet
- [ ] Scoped module baselines configured
- [ ] Customer Admin sign-off on structure
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Revert misconfigured pipelines/statuses to defaults; document changes; re-apply from worksheet after backup if destructive.

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
| **Previous SOP** | [SOP-ONB-003 — Module Licensing](SOP-ONB-003-module-licensing.md) |
| **Next SOP** | [SOP-ONB-005 — User Provisioning](SOP-ONB-005-user-provisioning.md) |
| **Related SOPs** | [SOP-ADM-006](../administration/SOP-ADM-006-workspace-configuration.md), [SOP-ADM-007](../administration/SOP-ADM-007-branding.md), [SOP-ADM-008](../administration/SOP-ADM-008-notifications.md) |
| **Related Documents** | [Org Admin Guide](../../onboarding/org-admin-guide.md), [Onboarding Playbook](../../onboarding/playbook.md) |
| **Required Forms** | Org structure worksheet |
| **Required Checklists** | Configuration steps from Onboarding Playbook |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
