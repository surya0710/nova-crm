# Deliverable 13 — Product Glossary

Every user-facing label should have **one official name**. UI copy, docs, and nav must prefer the Official term. Aliases may appear in search synonyms only.

---

## Core platform

| Official term | Definition | Do not use | Notes |
|---------------|------------|------------|-------|
| **Organization** | Tenant company account | Account, Company (in nav), Tenant (user UI) | “Tenant” OK in developer docs |
| **Workspace** | Top-level product area (CRM, HR, …) | Module (for top-level), App | New IA concept |
| **Module** | Capability area inside a workspace | — | e.g. Leave inside HR |
| **Home** | Personal landing dashboard | Main, Dashboard (ambiguous) | Route may stay `dashboard` |
| **Configuration Hub** | Central settings area | Organization Settings (as sole name), Settings dump | Short: Configuration |
| **User** | Login identity | Team member (ambiguous) | |
| **Users** | Admin list of org members | Team (for people admin) | Replaces sidebar “Team” |
| **Role** | Named permission set | Profile (for RBAC) | |
| **Permission** | Atomic capability | Right, Privilege | |
| **Plan** | Subscription tier | Package (UI) | |

---

## CRM & revenue

| Official term | Definition | Do not use | Notes |
|---------------|------------|------------|-------|
| **Lead** | Unqualified/qualified prospect record | Prospect (unless crm_term) | Respect `crm_term()` overrides |
| **Customer** | Account/customer master | Client, Company (for record type) | |
| **Opportunity** | Sales deal record | Deal (synonym OK in copy), Pipeline item | |
| **Pipeline** | Board/view of opportunities | — | View name, not entity |
| **Product** | Sellable item/service | SKU (secondary), Item | |
| **Quotation** | Commercial quote document | Quote (synonym OK), Proposal (unless term set) | |
| **Invoice** | Billing document | Bill | |
| **Payment** | Receipt against invoice(s) | Receipt (synonym), Transaction | |
| **Revenue** | Nav group for Quotes/Invoices/Payments | Finance (for ops group) | Finance = reports |
| **Assignment rule** | Auto-routing rule | Round robin (feature nickname) | |

Industry term overrides via `crm_term()` remain valid; glossary defines the default English product language.

---

## Work management

| Official term | Definition | Do not use | Notes |
|---------------|------------|------------|-------|
| **Task** | Work item | To-do, Ticket (until Support) | |
| **Project** | Delivery container | Job (ambiguous with recruitment) | |
| **Milestone** | Project checkpoint | Gate | |
| **Portfolio** | Strategic set of projects | Basket | |
| **Program** | Coordinated set of projects | — | |
| **Resource** | Allocatable person/capacity | Worker | |
| **Resources** | Capacity planning area | Resource Planner (as sole top name) | Planner is a view |
| **Risk** | Potential negative event | — | |
| **Issue** | Materialized problem | Bug (unless software context) | |
| **Baseline** | Frozen plan snapshot | — | |
| **Budget** | Project financial plan | — | |

---

## People & HR

| Official term | Definition | Do not use | Notes |
|---------------|------------|------------|-------|
| **Employee** | Internal workforce record | Staff, Resource (in HR UI) | |
| **Directory** | Read-oriented people list | Phonebook | |
| **Team** | HR team grouping | Group | Not “Users” |
| **Branch** | Org location unit | Office (unless synonym) | |
| **Department** | Org unit | Division (unless configured) | |
| **Designation** | Job title classification | Position title | |
| **Attendance** | Time presence records | Punch log | |
| **Shift** | Work schedule definition | Roster (view) | |
| **Leave** | Time-off domain | Time off (synonym OK), Vacation (type) | |
| **Leave application** | A leave request | Leave request (synonym OK) | |
| **Payroll** | Compensation processing | Salary run | |
| **Performance** | Goals/reviews domain | Appraisal (subprocess) | |
| **My HR** | Employee self-service area | Self-Service (section title) | ESS in engineering OK |
| **Exit process** | Offboarding case | Resignation workflow | |

---

## Talent

| Official term | Definition | Do not use | Notes |
|---------------|------------|------------|-------|
| **Recruitment** | Hiring domain | ATS (marketing only) | |
| **Requisition** | Headcount request | Req request | |
| **Job opening** | Published hiring slot | Job post | |
| **Candidate** | Person in hiring pipeline | Applicant (synonym) | |
| **Application** | Candidate ↔ opening link | — | |
| **Interview** | Evaluation event | — | |
| **Offer** | Employment offer | Offer letter (document) | |
| **Careers** | Public job site | Career page | |

---

## Automation, data, integrations

| Official term | Definition | Do not use | Notes |
|---------------|------------|------------|-------|
| **Workflow** | Automation definition | Zap, Macro | |
| **Custom Field** | User-defined field | Metadata field (UI) | Metadata OK in admin advanced/docs |
| **Integration** | Connected external system | Connector | |
| **Provider** | Marketing/integration vendor connection | Plugin | |
| **API token** | Machine access credential | Personal access token (if not PAT model) | |
| **Webhook** | HTTP callback endpoint | — | |

---

## Analytics & trust

| Official term | Definition | Do not use | Notes |
|---------------|------------|------------|-------|
| **Report** | Analytical view/export | Dashboard (for reports) | |
| **Finance** | Financial analytics area | Accounting (until GL) | |
| **Audit Log** | Security/activity trail | Activity log (if audit-specific) | |
| **Notification** | User alert | Alert (synonym), Bell item | |
| **Knowledge Center** | In-app documentation | Docs, Help Center | Help = entry point |

---

## External surfaces

| Official term | Definition | Do not use |
|---------------|------------|-------------|
| **Careers portal** | Candidate-facing site | Candidate CRM |
| **Platform** | SaaS operator console | Superadmin UI |

---

## Naming rules

1. **One term per concept** in primary UI.  
2. **View names ≠ entity names** (Pipeline vs Opportunity).  
3. **My …** prefix for employee-scoped mirrors of admin nouns.  
4. **Plural in nav lists**, singular on record pages.  
5. Prefer glossary over inventing clever labels.  
6. When `crm_term()` customizes CRM nouns, document org-specific terms in Knowledge; defaults remain this glossary.

---

## Rename backlog (Phase 14 copy)

| Current UI | Target |
|------------|--------|
| Team (settings) | Users |
| Metadata Fields | Custom Fields |
| Self-Service | My HR |
| Resource Planner (top-level) | Resources |
| Pipeline (if entity implied) | Opportunities |
| Organization Settings | Configuration |
| Dashboard (when ambiguous) | Home |
