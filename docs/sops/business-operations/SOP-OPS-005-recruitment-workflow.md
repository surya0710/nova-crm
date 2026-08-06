# SOP-OPS-005 — Recruitment Workflow

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-OPS-005 |
| **Title** | Recruitment Workflow |
| **Version** | 1.0 |
| **Effective Date** | 2026-08-06 |
| **Department** | Business Operations (Talent) |
| **Owner** | Recruiter / Hiring Manager |
| **Reviewer** | HR Administrator |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Run requisition-to-hire in Konnect Nex: job opening, candidate pipeline, interviews, offer, and handoff to employee onboarding—while preserving tenant isolation and RBAC.

## Scope

- **In scope:** Requisitions/openings, candidates, applications, interviews, evaluations, offers, candidate portal, analytics handoff.
- **Out of scope:** Post-hire employee master setup (SOP-OPS-001); careers site branding deep-dive.

## Preconditions

- [ ] Recruitment module licensed
- [ ] Interview stages / templates configured for the org
- [ ] Offer templates available (if using offer letters)
- [ ] Mail/queue healthy for candidate notifications

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| HR → Recruitment | Job/candidate/offer permissions | Policies |
| Careers / candidate portal | Candidate auth | Org-scoped middleware |
| APIs | Recruitment API + `api.access` | If integrations used |

## Step-by-step Procedure

### 1. Open the role

1. Create job requisition / job opening with department, location, and hiring manager.
2. Publish to careers portal when ready; confirm org slug scoping.

### 2. Source & screen

1. Add candidates manually or via application intake.
2. Move applications through stages; record evaluations.
3. Ensure recruiters only see current-org candidates (spot-check with second tenant in UAT).

### 3. Interview

1. Schedule interview rounds; invite panel.
2. Capture scorecards/feedback before stage advancement.
3. Calendar integrations (if enabled) must fail closed without leaking other orgs’ events.

### 4. Offer & accept

1. Create offer letter from template; route approvals if configured.
2. Send offer; track negotiation states.
3. On acceptance, create onboarding ticket and execute SOP-OPS-001.

### 5. Close & report

1. Mark opening filled/cancelled.
2. Review recruitment analytics for the period.
3. Confirm notifications and workflow events fired as expected.

## Validation Checklist

- [ ] Candidate portal URLs are organization-scoped
- [ ] Offer PDFs/downloads permission-checked
- [ ] API list endpoints paginated and org-scoped
- [ ] Rejected candidates cannot re-enter via IDOR
- [ ] Queue emails show correct branding/org

## Failure Handling

| Symptom | Action |
|---------|--------|
| Application webhook failures | Provider diagnostics; retry; see marketing/recruitment integration docs |
| Interview invite not sent | Queue + mail config (SOP-MON-002, SOP-DEP-003) |
| Offer approval stuck | Check approval permissions and workflow binding tenant |

## Related SOPs / Docs

- [SOP-OPS-001 — Employee Onboarding](SOP-OPS-001-employee-onboarding.md)
- [docs/recruitment/](../../recruitment/) (if present)
- Tests: `HrmsRecruitment*`, `RecruitmentApiTest`, `CandidatePortalTest`
