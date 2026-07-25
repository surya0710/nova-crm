# Deliverable 11 — Empty States & First-Time Experience

Empty, loading, denied, and onboarding states for workspace UX.

---

## Principles

1. **Explain** what this space is for.  
2. **Offer one primary action** (create, connect, invite, learn).  
3. **Never look broken** — empty ≠ error.  
4. **Role-aware** — employees don’t see “Add Employee”.  
5. **Progressive** — first-run checklists dismiss permanently per user/org.

---

## Empty dashboards

| Situation | Message pattern | CTA |
|-----------|-----------------|-----|
| No widgets enabled | “Your dashboard isn’t set up yet.” | Customize · Use role default |
| All widgets empty data | Keep widgets; each shows own empty | — |
| New user | Short checklist: complete profile, try search, open My Work | Dismissible |

Illustration: simple, on-brand, non-cartoonish; optional.

---

## Empty modules (lists)

| Module | Empty copy | Primary CTA | Secondary |
|--------|------------|-------------|-----------|
| Leads | “No leads yet. Capture your first prospect.” | Create Lead | Import |
| Customers | “No customers yet.” | Create Customer | From Lead |
| Opportunities | “Pipeline is empty.” | Create Opportunity | — |
| Projects | “No projects yet.” | Create Project | From template |
| Tasks | “You’re all caught up.” | Create Task | — |
| Employees | “No employees yet.” | Add Employee | Import |
| Candidates | “No candidates in this view.” | Add Candidate | Careers |
| Integrations | “No connections yet.” | Connect | Docs |

Filters active + zero rows: “No matches. Clear filters.” — not “create first X”.

---

## First login (user)

Steps:

1. Land on persona default workspace/home  
2. Welcome widget with org name  
3. Optional 3-step checklist: Profile · Notifications · Tour link to Knowledge  
4. Do not block with modal gauntlet  

---

## New organization

Owner checklist:

1. Organization profile / branding  
2. Invite users  
3. Configure working days (if HR)  
4. Create first Lead or Employee (industry-dependent)  
5. Connect integration (optional)  

Persist in Administration home until complete.

---

## First project / first employee

| Milestone | Experience |
|-----------|------------|
| First project | Success toast + “Add milestone” / “Invite members” next steps |
| First employee | Prompt ESS eligibility / invite user link |
| First hire from candidate | Confetti optional; show Employee record + “Set leave balances” |

---

## No search results

“No results for ‘{q}’ in {scope}.”

Actions:

- Search Everywhere (if scoped)  
- Clear query  
- Create {entity} if single-type intent  
- Knowledge search  

---

## Permission denied

Prefer **hide** nav items. If deep-linked:

- Title: “You don’t have access”  
- Explanation without leaking existence of sensitive names when policy requires  
- CTA: Go to Home · Request access (future) · Contact admin  

Do not use raw 403 page for in-app chrome routes when avoidable (align with existing empty-state work from Phase 11.8.1).

---

## Loading

| Surface | Pattern |
|---------|---------|
| Dashboard | Widget skeletons |
| List | Table skeleton / progressive rows |
| Entity | Header + tab skeleton |
| Search | Debounced; spinner in field after 300ms |
| Infinite feed | Bottom spinner |

Never block entire app shell — sidebar/chrome stay interactive.

---

## Error (distinct from empty)

“Something went wrong.” + Retry + Support/Knowledge link. Log reference id for admins.

---

## Checklist component (shared)

```
[ ] Step label
[x] Completed step
Progress 2/4
```

Used for: new org, first project setup, recruitment opening launch.

---

## Anti-patterns

- Empty state that only says “No data”  
- Primary CTA the user cannot permission  
- Blocking tours that cannot be skipped  
- Blaming the user for permission errors  
