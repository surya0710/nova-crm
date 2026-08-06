# SOP-OPS-006 — CRM Setup

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-OPS-006 |
| **Title** | CRM Setup |
| **Version** | 1.0 |
| **Effective Date** | 2026-08-06 |
| **Department** | Business Operations (Revenue) |
| **Owner** | Customer Admin / Revenue Ops |
| **Reviewer** | Implementation Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Configure a tenant CRM so leads, customers, opportunities, activities, imports, and dashboards operate under dynamic RBAC and `LeadVisibilityService` ownership rules.

## Scope

- **In scope:** Pipelines/stages basics, teams, assignment, metadata/custom fields, saved filters, import templates, notification preferences, CRM home widgets.
- **Out of scope:** Marketing provider OAuth deep setup; quotation/finance legal configuration.

## Preconditions

- [ ] Organization provisioned; CRM module licensed
- [ ] Admin user has CRM configuration permissions
- [ ] Queue available if imports will be queued

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| CRM settings / metadata | Metadata + CRM manage | |
| Teams / assignment | Assignments settings | `/organization/settings/assignments` |
| Import center | Import permissions | |
| Users / roles | RBAC manage | Permission templates |

## Step-by-step Procedure

### 1. Roles & visibility

1. Confirm sales roles use permission templates (not ad-hoc role-name logic).
2. Spot-check that lead list/detail/API/search/export all honor `LeadVisibilityService`.
3. Configure teams and managers for reporting hierarchy.

### 2. Data model

1. Review lead/customer statuses and opportunity stages for the business.
2. Add required custom fields via Metadata Platform; publish to forms.
3. Create saved filters for common queues (new leads, follow-ups).

### 3. Assignment & intake

1. Configure assignment settings under Organization Settings.
2. Validate lead intake API tokens (if used) are org-scoped and throttled.
3. Dry-run import template download → small CSV import with duplicate strategy.

### 4. Collaboration & reporting

1. Enable activity/timeline expectations for sales users.
2. Confirm CRM home widgets load under dashboard cache.
3. Verify exports respect visibility (no “all leads” leak for restricted users).

### 5. Go-live smoke

1. Create lead → activity → opportunity → customer path once.
2. Switch to a restricted user; confirm hidden leads stay hidden.
3. Confirm notifications for assignment/follow-up if enabled.

## Validation Checklist

- [ ] `LeadVisibilityService` is the only listing authority for leads
- [ ] Cross-tenant lead IDs return 404/403
- [ ] Import rejects cross-org owners (diagnostics)
- [ ] Metadata filters work on lead & customer indexes
- [ ] Workflow bindings (if any) are tenant-isolated

## Failure Handling

| Symptom | Action |
|---------|--------|
| Import queued forever | Queue health SOP-MON-002; import session status |
| Metadata filter empty results | Confirm field published + index; see stabilization bugfix docs |
| Wrong leads visible | Re-check permissions + ownership; do not bypass with role-name hacks |

## Related SOPs / Docs

- [SOP-ONB-004 — Organization Configuration](../onboarding/SOP-ONB-004-organization-configuration.md)
- [SOP-ONB-006 — Initial Data Import](../onboarding/SOP-ONB-006-initial-data-import.md)
- Stabilization: `docs/STABILIZATION_BUGFIX_01` … `_03`
