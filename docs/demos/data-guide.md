# Demo Data Guide

## Accounts

| Email | Role (typical) | Password |
|-------|----------------|----------|
| `demo@novacrm.test` | Organization owner / demo lead | `password` |

Additional employee users are created by `PresentationDemoSeeder` with email patterns from the roster (also `password` unless changed).

> **Security:** Demo credentials are for non-production only. Never use these passwords in production.

## What gets seeded

| Area | Contents |
|------|----------|
| Org structure | Branches (Mumbai HQ + Bengaluru), departments, designations, employees, managers |
| HRMS | Shifts, attendance, leave types/balances/applications, assets, documents, announcements |
| Recruitment | Requisitions, openings, candidates, applications, interviews, offers |
| Projects | Projects, milestones, tasks, budgets, members, resources/workload |
| CRM | Leads, customers, products, opportunities, quotations, invoices, payments |
| Marketing | Campaigns (active/paused/completed) + demo provider connections |
| Analytics | Dashboard provisioning for the org |

## Demo workflows (quick paths)

1. **CRM revenue path:** Lead → Opportunity → Quotation → Invoice → Payment  
2. **Delivery path:** Project → Milestone → Task → comment/time  
3. **People path:** Employee → Leave application → Attendance snapshot  
4. **Hire path:** Job opening → Application → Interview → Offer  
5. **Marketing path:** Campaign list → attribution / analytics workspace  

## Reset procedures

### Safe re-seed (idempotent)
```bash
php artisan demo:seed-presentation
```
Seeder skips if Nova Enterprises already has a full employee roster.

### Full refresh (local/demo DB only)
Only on disposable local databases, and only with explicit approval:

```bash
# DESTRUCTIVE — local demo DB only
php artisan migrate:fresh --seed
php artisan demo:seed-presentation
```

**Do not** run destructive refresh against shared development or production databases.

### Partial cleanup
Prefer updating demo narrative in place. If a specific dataset is corrupted, fix via admin UI or targeted seeder methods rather than wiping the tenant.