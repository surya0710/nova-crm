# Onboarding Playbook

**Owner:** Implementation · **Version:** 1.0 · **Last reviewed:** 2026-07-25

## Timeline (typical)

| Phase | Duration | Outcome |
|-------|----------|---------|
| Kickoff | Day 0–2 | Success plan, roles, schedule |
| Provision | Day 1–3 | Org live, admins invited |
| Configure | Day 3–10 | Modules match Order Form |
| Import & UAT | Day 8–15 | Data validated, UAT signed |
| Train & go-live | Day 12–20 | Users trained, checklist signed |

Adjust for seat count and import complexity.

## Phase A — Kickoff

- [ ] Review Order Form (modules, seats, start date, SLAs)
- [ ] Confirm Customer Admin and technical contact
- [ ] Confirm success metrics from discovery
- [ ] Share Knowledge Center and support channel
- [ ] Schedule configuration workshops

## Phase B — Provisioning

Platform Operator (`/platform`):

- [ ] Create organization (name, slug, timezone, currency, locale)
- [ ] Assign plan / subscription matching Order Form
- [ ] Activate organization status
- [ ] Invite Customer Admin (organization-owner or admin role)
- [ ] Verify admin can authenticate and switch into org context

## Phase C — Foundation configuration

Customer Admin + Implementation:

- [ ] **Branding** — logo, display name, colors (Administration → Branding)
- [ ] **Branches** — HQ + remote sites
- [ ] **Departments** — aligned to customer org chart
- [ ] **Roles & permissions** — map job roles to Konnect Nex roles; least privilege
- [ ] **Users** — create / invite initial cohort (admins, managers, power users)
- [ ] **Holiday calendar** — regional holidays
- [ ] **Leave types** — policy-aligned leave catalog

## Phase D — Module configuration

Configure only modules on the Order Form:

### CRM
- [ ] Lead sources and statuses reviewed
- [ ] Pipeline stages confirmed
- [ ] Products / SKUs loaded
- [ ] Sample quotation numbering / tax defaults understood

### Projects
- [ ] Project types, statuses, labels
- [ ] Default templates / foundations seeded
- [ ] Portfolio / program structure (if licensed)

### HRMS
- [ ] Shifts and attendance rules
- [ ] Leave balances approach agreed
- [ ] Recruitment stages (if licensed)
- [ ] Payroll prerequisites documented

### Marketing
- [ ] Providers / credentials as scoped
- [ ] Campaign naming / UTM conventions

### Analytics
- [ ] Confirm workspace access for executives
- [ ] KPI library walkthrough scheduled

## Phase E — Integrations & import

- [ ] Provider integrations tested in staging or limited prod
- [ ] Import templates completed (employees, customers, leads, etc.)
- [ ] Dry-run import; fix errors; final import
- [ ] Spot-check counts and sample records with Customer Admin

## Phase F — Validation (UAT)

- [ ] Customer executes scripted UAT scenarios (see go-live checklist)
- [ ] Defects triaged (blocker vs defer)
- [ ] UAT sign-off captured in writing

## Phase G — Go-live

Execute [go-live-checklist.md](go-live-checklist.md). Hand to CS for welcome cadence.