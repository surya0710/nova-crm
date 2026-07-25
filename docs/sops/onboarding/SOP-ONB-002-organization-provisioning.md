# SOP-ONB-002 — Organization Provisioning

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-ONB-002 |
| **Title** | Organization Provisioning |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Onboarding |
| **Owner** | Platform Operator |
| **Reviewer** | Implementation Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Create and activate the customer organization record so licensed modules and users can be configured.

## Scope

- **In scope:** Org record creation, activation status, and initial admin invitation.
- **Out of scope:** Module licensing details (SOP-ONB-003) and deep configuration (SOP-ONB-004).

## Preconditions

- [ ] Handoff ticket with legal entity name and primary admin email
- [ ] Platform console access

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| `/platform` Organizations | Platform Operator | Create org |

## Step-by-step Procedure

### 1. Create organization

1. Open Platform → Organizations → Create.
2. Enter legal name, display name, timezone, and locale from Order Form.
3. Set status to Active (or Trial if billing SOP requires trial path).

### 2. Invite initial admin

1. Invite the named Customer Admin email.
2. Confirm invitation email delivered; record org ID on the onboarding ticket.

## Validation Checklist

- [ ] Organization record exists and is Active/Trial as ordered
- [ ] Org ID recorded on ticket
- [ ] Initial admin invitation sent
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If wrong entity was created, disable the org (do not delete until SOP-OFF path if data exists), create the correct org, and update the ticket.

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
| **Previous SOP** | [SOP-ONB-001 — Customer Onboarding](SOP-ONB-001-customer-onboarding.md) |
| **Next SOP** | [SOP-ONB-003 — Module Licensing](SOP-ONB-003-module-licensing.md) |
| **Related SOPs** | [SOP-ONB-003](SOP-ONB-003-module-licensing.md), [SOP-ADM-002](../administration/SOP-ADM-002-organization-administration.md), [SOP-BIL-001](../billing/SOP-BIL-001-new-subscription.md) |
| **Related Documents** | [Platform Admin Guide](../../onboarding/platform-admin-guide.md) |
| **Required Forms** | Order Form entity block |
| **Required Checklists** | Org provisioning fields checklist (name, timezone, locale, admin email) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
