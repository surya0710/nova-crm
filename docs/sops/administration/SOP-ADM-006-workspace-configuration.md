# SOP-ADM-006 — Workspace Configuration

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ADM-006 |
| **Title** | Workspace Configuration |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Administration |
| **Owner** | Org Admin / Implementation |
| **Reviewer** | Implementation Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Configure workspace defaults and module settings that shape daily user experience.

## Scope

- **In scope:** Workspace home defaults, module toggles already licensed, and operational defaults.
- **Out of scope:** Branding visuals (SOP-ADM-007) and notification channels (SOP-ADM-008).

## Preconditions

- [ ] Modules licensed
- [ ] Customer preferences captured at kickoff

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Organization / workspace settings | Org Admin | Configure defaults |

## Step-by-step Procedure

### 1. Apply defaults

1. Configure workspace defaults per [workspaces product docs](../../product/workspaces.md) where applicable.
2. Align CRM/Projects/HRMS defaults with kickoff worksheet.
3. Confirm navigation and home experience for sample roles.

## Validation Checklist

- [ ] Defaults match kickoff worksheet
- [ ] Sample roles see expected home/navigation
- [ ] Customer Admin accepts configuration
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Restore previous defaults from notes; document deviation.

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
| **Previous SOP** | [SOP-ADM-005 — Permission Management](SOP-ADM-005-permission-management.md) |
| **Next SOP** | [SOP-ADM-007 — Branding](SOP-ADM-007-branding.md) |
| **Related SOPs** | [SOP-ONB-004](../onboarding/SOP-ONB-004-organization-configuration.md), [SOP-ADM-007](SOP-ADM-007-branding.md) |
| **Related Documents** | [Workspaces](../../product/workspaces.md), [Org Admin Guide](../../onboarding/org-admin-guide.md) |
| **Required Forms** | Kickoff worksheet |
| **Required Checklists** | Workspace defaults checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
