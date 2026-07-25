# Deliverable 13 — Documentation Validation

Every operational task for pilot onboarding must be completable from documentation alone.

## Review matrix

| Document set | Path | Usable end-to-end? | Gaps |
|--------------|------|--------------------|------|
| SOPs | `docs/sops/` | Yes | None blocking |
| Customer manuals / entry | `docs/onboarding/` | Yes | — |
| Administrator guides | `org-admin-guide.md`, `platform-admin-guide.md` | Yes | — |
| Deployment guides | `docs/deployment/` | Yes | Infra specifics host-dependent |
| Troubleshooting | `docs/troubleshooting/` | Yes | — |
| Pilot program | `docs/launch/` (this pack) | Yes | Expanded in 15.8 |
| Data import SOP | SOP-ONB-006 | Partial | Overstates HRMS/Projects CSV — see ISSUE-P15.8-001 |

## Updates made in 15.8

- Expanded `docs/launch/` evidence pack (profiles, CAT, security, performance, deploy/ops reports, issue/risk registers, GA recommendation).
- Added `pilot:seed` / `PilotCustomerSeeder` operator path in launch README.
- Sample CRM CSVs under `docs/launch/datasets/`.

## Recommended follow-up (non-blocking)

1. Amend SOP-ONB-006 to distinguish Import Platform entities (Leads, Customers) vs manual/API seeding for HRMS/Projects.
2. Attach completed CAT checklists to each live pilot ticket.
3. Append staging/production deploy evidence to [deployment-validation-report.md](./deployment-validation-report.md) before infra GA gate.
