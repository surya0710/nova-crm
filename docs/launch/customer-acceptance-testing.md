# Deliverable 10 — Customer Acceptance Testing (CAT)

Structured acceptance matrix for pilot users. Execute after `php artisan pilot:seed`.

**Password:** `password` for all seeded accounts.

## Participants

| Org | Owner | Manager | HR | Sales | Employee |
|-----|-------|---------|----|-------|----------|
| A | owner@apexsales.test | manager@apex-sales-partners.test | hr@… | sales@… | employee@… |
| B | owner@meridianpeople.test | manager@meridian-people-works.test | hr@… | sales@… | employee@… |
| C | owner@cascadegrowth.test | manager@cascade-growth-labs.test | hr@… | sales@… | employee@… |
| D | owner@harbordelivery.test | manager@harbor-delivery-collective.test | hr@… | sales@… | employee@… |
| E | owner@summitenterprise.test | manager@summit-enterprise-group.test | hr@… | sales@… | employee@… |

## Test matrix

| ID | Area | Steps | Expected | A | B | C | D | E |
|----|------|-------|----------|---|---|---|---|---|
| CAT-01 | Authentication | Login as owner | Session established | ☐ | ☐ | ☐ | ☐ | ☐ |
| CAT-02 | Navigation | Open workspace switcher | Only licensed workspaces | ☐ | ☐ | ☐ | ☐ | ☐ |
| CAT-03 | Dashboard | Open home/dashboard | Widgets load; no unlicensed module widgets | ☐ | ☐ | ☐ | ☐ | ☐ |
| CAT-04 | CRUD | Create/edit one record in a licensed module | Persists; audit/org scoped | ☐ | ☐ | ☐ | ☐ | ☐ |
| CAT-05 | Notifications | Open notification prefs / bell | Preferences exist post-upgrade | ☐ | ☐ | ☐ | ☐ | ☐ |
| CAT-06 | Search | Global search for seeded entity | Results within tenant | ☐ | ☐ | ☐ | ☐ | ☐ |
| CAT-07 | Reports | Open module report/analytics if licensed | Loads or correctly denied | ☐ | ☐ | ☐ | ☐ | ☐ |
| CAT-08 | RBAC | Login as employee; open admin settings | Denied | ☐ | ☐ | ☐ | ☐ | ☐ |
| CAT-09 | Unlicensed URL | Hit unlicensed module route | Blocked | ☐ | ☐ | ☐ | ☐ | ☐ |
| CAT-10 | Performance feel | Dashboard + list pages | Acceptable on local hardware | ☐ | ☐ | ☐ | ☐ | ☐ |

## Observations

| ID | Org | Role | Observation | Severity | Linked issue |
|----|-----|------|-------------|----------|--------------|
| | | | | | |

## Sign-off

| Role | Name | Date | ☐ |
|------|------|------|---|
| Implementation | | | |
| Customer Admin (pilot) | | | |
| CS | | | |
