# HRMS Product Introduction Video — Work Plan

**Product:** Konnect Nex HRMS  
**Source application:** `C:\xampp\htdocs\nova-crm`  
**Demo tenant:** Nova Enterprises (dedicated presentation/demo organization)  
**Target length:** 10–15 minutes  
**Export:** `intro/HRMS_Product_Introduction.mp4` (1920×1080, 16:9, H.264, 30 FPS)

---

## 1. Objective

Create a professional product-introduction and walkthrough video using **actual screen recordings** of the running HRMS application. No slideshow-only video, mock UI, fabricated screenshots, or placeholder screens.

---

## 2. Pre-Recording Preparation

### Application readiness

| Check | Status / notes |
|-------|----------------|
| App boots | Laravel 12; `php artisan serve --host=127.0.0.1 --port=8000` |
| MySQL/MariaDB | XAMPP MySQL on `127.0.0.1:3306` |
| Migrations | Forward migrations applied (including geo + WFH) |
| Demo seed | `php artisan demo:seed-presentation` |
| Video prep | `php intro/scripts/prepare-hrms-demo.php` |
| Recording | `node intro/scripts/record-hrms-video.mjs` |
| Assembly | `node intro/scripts/assemble-hrms-video.mjs` |

### Demo accounts (local/demo only)

| Persona | Email | Password |
|---------|-------|----------|
| Super Admin / Org Owner | `demo@novacrm.test` | `password` |
| HR | `neha.gupta@novacrm.test` | `password` |
| Manager | `priya.sharma@novacrm.test` | `password` |
| Employee / ESS | `arjun.kapoor@novacrm.test` | `password` |
| Recruiter (HR) | `pooja.saxena@novacrm.test` | `password` |

### Demo data coverage

- Organization structure: branches, departments, designations, shifts, employees
- Attendance history + geofences + verification mode
- WFH policies, permanent assignment, pending multi-day request
- Leave types / balances / applications
- Recruitment requisitions → openings → candidates → interviews → offers
- Payroll components / structure / assignment / open period
- RBAC roles & permission matrix
- Audit log surfaces

**Production data is not modified.** Prep scripts are additive/idempotent against the Nova Enterprises demo tenant.

---

## 3. Recording Rules

### Mandatory

- Record the real browser/application UI via Playwright video capture
- Use realistic Nova Enterprises demo data
- Keep cursor movement visible where useful
- Navigate naturally through current HRMS/ESS navigation
- Show forms, tables, dashboards, detail pages, and approval queues
- Demonstrate HR/admin, manager, and employee experiences
- Show successful workflows and live pages (not only static lists)
- Never display real passwords, API keys, tokens, or private customer secrets

### Avoid

- Fake screenshots / Figma mockups / AI-generated UI
- Unexplained code walkthroughs
- Terminal output as primary content
- Presenting deferred features (e.g. optional WFH map) as completed
- Claiming vendor-specific biometric hardware integrations that are not configured

---

## 4. Video Structure

| # | Scene | Primary surfaces |
|---|-------|------------------|
| 1 | Product Introduction | Login, dashboard, HRMS workspace |
| 2 | HRMS Foundation | Employees, profile, org structure, shifts, holidays, leave types |
| 3 | Employee Management | Directory, create/edit, assignments, documents |
| 4 | Attendance | Dashboard, calendar, summary, reports, record detail |
| 5 | Geo-Attendance | Attendance rules, geofences, verification metadata |
| 6 | Work From Home | WFH policies, assignments, requests, approval queue |
| 7 | Leave | Types, balances, applications, approval queue |
| 8 | Payroll | Dashboard, components, structures, assignments, periods, payslips |
| 9 | Tax / TDS | Tax workspace + statutory surfaces |
| 10 | Recruitment | Requisitions → openings → candidates → interviews → offers → analytics |
| 11 | ESS | Employee dashboard, attendance, leave, WFH, documents, payroll |
| 12 | Manager | Team visibility + leave/WFH approval queues |
| 13 | RBAC & Security | Roles, permission matrix, org settings hub |
| 14 | Workflow & Audit | WFH approval history + audit logs |
| 15 | Closing | HRMS dashboard + feature montage |

Narration cues are listed in `HRMS_FEATURE_TIMELINE.txt`.

---

## 5. Tooling

| Tool | Purpose |
|------|---------|
| Playwright Chromium | Actual UI navigation + `.webm` screen recordings |
| FFmpeg | Title cards, normalize, concat, H.264 MP4 export |
| `prepare-hrms-demo.php` | Demo users, WFH/geo/payroll readiness |
| `record-hrms-video.mjs` | Scene-by-scene live capture |
| `assemble-hrms-video.mjs` | Post-production assembly |

---

## 6. Post-Production

1. Strip empty/failed scene folders
2. Insert section title cards
3. Normalize all clips to 1920×1080 @ 30 FPS
4. Concatenate intro → scenes → outro
5. Export `intro/HRMS_Product_Introduction.mp4`
6. Review end-to-end against quality gate

Sensitive handling:

- Password fields are cleared/redacted during capture where possible
- Demo `.test` emails only
- No API tokens or developer secrets shown intentionally

---

## 7. Deliverables

```
intro/
├── HRMS_PRODUCT_INTRO_VIDEO_PLAN.md
├── HRMS_FEATURE_TIMELINE.txt
├── HRMS_Product_Introduction.mp4
├── raw/                      # scene webm captures
├── assets/                   # title cards + normalized clips
└── scripts/
    ├── prepare-hrms-demo.php
    ├── record-hrms-video.mjs
    └── assemble-hrms-video.mjs
```

---

## 8. Quality Gate

The video is complete only when:

- [x] Actual application screens are used
- [x] HR/admin flow is demonstrated
- [x] Manager flow is demonstrated
- [x] Employee/ESS flow is demonstrated
- [x] Attendance is demonstrated
- [x] Geo-attendance is demonstrated
- [x] WFH is demonstrated
- [x] Leave is demonstrated
- [x] Payroll is demonstrated
- [x] Recruitment is demonstrated
- [x] RBAC/security is demonstrated
- [x] Workflow/audit is demonstrated
- [x] Deferred features are not falsely presented as available
- [x] Sensitive secrets are avoided/redacted
- [x] Final MP4 reviewed end-to-end after assembly

---

## 9. Rebuild Commands

```bash
# Services
# Start XAMPP MySQL + Apache as needed

php artisan migrate
php artisan demo:seed-presentation
php intro/scripts/prepare-hrms-demo.php

php artisan serve --host=127.0.0.1 --port=8000

node intro/scripts/record-hrms-video.mjs
node intro/scripts/assemble-hrms-video.mjs
```
