# Deliverable 11 — Primary Business Flows

End-to-end journeys across Konnect Nex modules. Decision points, pain points, and cross-module interactions.

---

## Flow A — Revenue: Lead → Payment

```
Lead → Opportunity → Customer → Quotation → Invoice → Payment
```

### Stages

| Stage | Module | User | Key actions |
|-------|--------|------|-------------|
| Capture | Leads | Sales Exec | Create/import lead, qualify |
| Convert | Leads → Customers / Opportunities | Sales Exec | Convert lead |
| Pursue | Opportunities | Sales Exec/Mgr | Advance stages, attach products |
| Propose | Quotations | Sales | Create/send quote from opportunity/customer |
| Commit | Customers | Sales | Ensure customer master exists |
| Bill | Invoices | Sales/Finance | Create invoice from quote or manually |
| Collect | Payments | Finance | Record payment, reconcile |

### Decision points

- Disqualify lead vs convert  
- Create opportunity without customer vs require customer  
- Quote accepted → invoice automatically vs manual  
- Partial payments vs full settlement  
- Assignment rule picks owner on create/import  

### Pain points (current)

- Assignment settings not in CRM nav  
- Pipeline vs Opportunity terminology  
- Import buried  
- Customer 360 incomplete (projects/invoices tabs uneven)  
- Finance report disconnected from invoice list  

### Cross-module interactions

- Workflows on lead/opportunity status  
- Metadata custom fields on lead/customer  
- Marketing attribution → lead source  
- Projects may start from won opportunity/customer  
- Tasks for follow-ups  

### Target UX

CRM workspace linear shortcuts; Opportunity page actions: Create Quote · Create Project; Invoice page: Record Payment.

---

## Flow B — Talent: Recruitment → Employee → HR lifecycle

```
Requisition → Opening → Candidate → Interview → Offer → Employee → HR (Attendance/Leave/Payroll/Performance) → Exit
```

### Stages

| Stage | Module | User | Key actions |
|-------|--------|------|-------------|
| Demand | Recruitment | Hiring Mgr/HR | Raise requisition |
| Publish | Recruitment / Careers | Recruiter | Open job, careers site |
| Attract | Careers portal | Candidate | Apply |
| Screen | Recruitment | Recruiter | Advance application stages |
| Interview | Recruitment | Panel | Evaluate |
| Offer | Recruitment | Recruiter/HR | Offer, negotiate, approve |
| Hire | Employees | HR | Create employee from candidate |
| Operate | ESS + HR | Employee/HR | Attendance, leave, payroll |
| Grow | Performance | Mgr/HR | Goals, reviews |
| Exit | Exit Processes | HR | Offboard |

### Decision points

- Reject vs hold vs advance candidate  
- Offer approval chain  
- Internal transfer vs new hire  
- ESS eligibility  

### Pain points

- Recruitment is one sidebar link; deep IA invisible  
- Hire handoff to Employee may feel manual  
- Dual Attendance/Leave labels (HR vs ESS)  
- Careers settings buried  

### Cross-module interactions

- Marketing/job providers  
- Notifications/mentions for interviewers  
- Resource planner may use employees  
- User account provisioning on hire (Team)  

### Target UX

HR → Recruitment secondary nav; “Convert to Employee” guided action; post-hire checklist.

---

## Flow C — Delivery: Project lifecycle

```
Idea → Project → Planning → Execution → Progress → Close → Portfolio rollup
```

### Stages

| Stage | Module | User | Key actions |
|-------|--------|------|-------------|
| Initiate | Projects | PM | Create from template/customer |
| Plan | Projects / Tasks / Resources | PM | Milestones, Gantt, allocations, baseline |
| Execute | Tasks / Collaboration | Team | Complete work, time logs, mentions |
| Monitor | Progress / Health / Risks / Issues | PM | Updates, risk register |
| Control | Budgets / Baselines | PM | Budget vs actual |
| Report | Project/Portfolio reports | PM/PMO | Stakeholder packs |
| Roll up | Programs / Portfolios | PMO | Executive views |

### Decision points

- Template vs blank project  
- In-house vs customer-linked  
- Escalate risk vs issue  
- Re-baseline  

### Pain points

- Portfolios/Programs/Risks not in sidebar  
- Resource views fragmented  
- Project automation separate from global Workflows  
- Tasks under CRM section confusing  

### Cross-module interactions

- Customer/Opportunity origin  
- Employees as resources  
- Invoices/budgets (commercial)  
- Workflows on status  

### Target UX

Projects workspace; Planning checklist; Portfolio path visible to PMO roles only.

---

## Flow D — Time off

```
Policy setup → Balance accrual → Employee request → Manager approve → Attendance impact → Payroll
```

### Decision points

- Approver chain  
- Conflict with holidays/attendance  
- Rejection vs modify dates  

### Pain points

- Four leave sidebar items  
- Policy in Settings vs ops in HR  

### Target UX

Single Leave hub with My / Team / Admin tabs.

---

## Flow E — Quote-to-cash exception paths

- Quote rejected → revise opportunity  
- Invoice overdue → Finance report + collections tasks  
- Credit/partial pay → payment allocations  

---

## Flow F — Automation-assisted lead routing

```
Lead created → Assignment rules → Owner notified → Workflow actions → Sales follow-up
```

### Pain points

- Assignments orphaned from nav  
- Workflows only under Settings  

### Target UX

CRM Config → Assignment; Automation Hub for global rules.

---

## Cross-flow map

```
Marketing ──► Lead ──► Opportunity ──► Customer ──┬──► Invoice ──► Payment
                                                  └──► Project ──► Portfolio
Recruitment ──► Employee ──► Resource allocations ──► Project
Employee ──► Leave/Attendance ──► Payroll
```

---

## Shared decision & UX requirements

1. **Guided next step** on every terminal status (Won, Hired, Completed).  
2. **Visible ownership** (assignee) on every stage.  
3. **Audit trail** for compliance stages (offer, payment, exit).  
4. **Search** must find records mid-flow by name/number.  
5. **Permissions** enforced at each handoff.
