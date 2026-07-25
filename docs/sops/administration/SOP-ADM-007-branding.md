# SOP-ADM-007 — Branding

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ADM-007 |
| **Title** | Branding |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Administration |
| **Owner** | Customer Admin |
| **Reviewer** | Implementation Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Apply customer branding (logo, colors, company profile) so the tenant reflects the customer identity.

## Scope

- **In scope:** Logo upload, color settings, and company profile fields.
- **Out of scope:** Custom domain/SSL (Deployment SOPs).

## Preconditions

- [ ] Brand assets received (logo SVG/PNG, brand colors)
- [ ] Customer Admin available to approve preview

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Organization branding settings | Org Admin | Upload assets |

## Step-by-step Procedure

### 1. Upload and configure

1. Upload logo meeting size/format guidance in Org Admin Guide.
2. Set brand colors if supported.
3. Update company profile display fields.

### 2. Accept

1. Customer Admin reviews UI preview and signs off on ticket.

## Validation Checklist

- [ ] Logo renders correctly on desktop and mobile shell
- [ ] Company profile accurate
- [ ] Customer Admin sign-off recorded
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Remove logo / revert colors to prior assets stored on ticket.

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
| **Previous SOP** | [SOP-ADM-006 — Workspace Configuration](SOP-ADM-006-workspace-configuration.md) |
| **Next SOP** | [SOP-ADM-008 — Notifications](SOP-ADM-008-notifications.md) |
| **Related SOPs** | [SOP-ONB-004](../onboarding/SOP-ONB-004-organization-configuration.md), [SOP-DEP-009](../deployment/SOP-DEP-009-domain-configuration.md) |
| **Related Documents** | [Org Admin Guide](../../onboarding/org-admin-guide.md) |
| **Required Forms** | Brand asset pack |
| **Required Checklists** | Branding acceptance checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
