# Release 1.2.4 — Employee Profile & Career Management

**Release:** 1.2.4  
**Priority:** High  
**Status:** Implemented  

---

## Objective

Transform the Employee Profile into the single source of truth for every employee — personal details, employment history, skills, projects, attendance, and career information — without introducing Payroll or Performance Management.

---

## Design principle

```
Controllers → Form Requests → Services → Models
```

- Extend the existing Employee module.
- No duplicate employee information.
- Timeline aggregates existing events (audits + operational tables).
- Dynamic RBAC (`hrms.*`, `ess.access`, `manager.dashboard`) and organization isolation (`BelongsToOrganization`).

---

## What was delivered

### 1. Skills

Table `employee_skills` with proficiency (Beginner → Expert), years of experience, last used, notes.

**Service:** `EmployeeSkillService`  
**Routes:** `hrms.employees.skills.*`  
Also syncable on employee create/update.

### 2. Certifications

Table `employee_certifications` with issuer, credential number/URL, issue/expiry, status.

Display status derived dynamically: **Active · Expiring Soon · Expired** (`certification_expiring_soon_days`).

**Service:** `EmployeeCertificationService`

### 3. Education / Experience / Emergency contacts

Existing tables extended:

| Area | Additions |
|------|-----------|
| Education | start/end dates, grade, description (specialization = `field_of_study`) |
| Experience | employment_type, technologies (designation = `title`) |
| Emergency | alternate_mobile, address; primary flag |

Dedicated services: `EmployeeEducationService`, `EmployeeExperienceService`.  
Multi-row editors on employee form; nested CRUD routes for incremental edits.

### 4. Employee Timeline

`EmployeeTimelineService` aggregates (no separate event store):

- Join / profile / org structure audits  
- Leave, attendance corrections  
- Document uploads & verification  
- Assets, exit processes  
- Project & task assignments (via linked user)  
- Login activity audits  

### 5. Profile dashboard summaries

`EmployeeProfileService` powers the employee show page:

| Method | Content |
|--------|---------|
| `profileCompletion` | Weighted section score |
| `currentWorkSummary` | Projects, tasks, sprint, hours, workload |
| `attendanceSummary` | Month KPIs + % (reuses `AttendanceCalendarService`) |
| `leaveSummary` | Balances, pending, upcoming, history (reuses `LeaveService`) |
| `reportingStructure` | Manager → Dept head → HR → reportees |
| `upcomingHolidays` | Via `LeaveService::getHolidaysForEmployee` |

### 6. Profile completion

Configurable weights in `config/hrms.php` → `profile_completion.sections`.  
Shown on employee profile and as a dashboard widget.

### 7. Dashboard widgets

Registered under HRMS section in `config/dashboard.php`:

- `profile_completion`
- `reporting_manager`
- `team_members`
- `upcoming_holidays`
- `current_projects_profile`

Existing widgets (`leave_balance`, `employee_attendance`, `my_projects`, recent activity) remain the shared ESS/HR building blocks.

---

## Architecture map

| Layer | Components |
|-------|------------|
| Controllers | `EmployeeController`, `EmployeeCareerController`, `EmployeeTimelineController` |
| Requests | `CreateEmployeeRequest` (+ career arrays), `StoreEmployee*Request` |
| Services | `EmployeeProfileService`, `EmployeeSkillService`, `EmployeeCertificationService`, `EmployeeEducationService`, `EmployeeExperienceService`, `EmployeeTimelineService` |
| Models | `EmployeeSkill`, `EmployeeCertification` + extended education/experience/emergency |
| Migration | `2026_07_28_230000_extend_employee_profile_career.php` |

---

## Testing

`tests/Feature/EmployeeProfileCareerTest.php`

- Skills / certifications / education / experience / emergency CRUD  
- Profile completion + reporting hierarchy  
- Show page enrichment + RBAC  
- Timeline aggregation + org isolation  
- Employee update sync of career sections  

```bash
php artisan test --filter=EmployeeProfileCareerTest
```

---

## Acceptance criteria

| Criterion | Status |
|-----------|--------|
| Profile is central HR record | ✓ |
| Skills, certifications, education, experience managed | ✓ |
| Timeline aggregates existing modules | ✓ |
| Attendance / leave reuse services | ✓ |
| Current projects & workload visible | ✓ |
| Reporting hierarchy displayed | ✓ |
| Profile completion calculated dynamically | ✓ |
| No duplicate employee data / business logic | ✓ |

---

## Next release

**Release 1.2.5 — HRMS Final Stabilization & Production Readiness**

- UX polish across HRMS  
- Employee self-service refinements  
- Cross-module regression testing  
- Performance optimization  
- Permission audit  
- Mobile responsiveness  
- Final production QA  
