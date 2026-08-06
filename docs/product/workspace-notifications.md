# Deliverable 9 — Workspace Notifications

Unified notification experience with workspace-aware grouping.

---

## Purpose

Deliver **actionable** awareness. Notifications are not a second activity feed — prefer items that need a response or confirm a critical outcome.

---

## Inbox structure

Global Notifications panel / page sections:

| Section | Contents |
|---------|----------|
| **Inbox** | All, newest first |
| **Unread** | Unread only |
| **Priority** | High priority / SLA / security |
| **Mentions** | `@` mentions |
| **Assignments** | Ownership changes, task assigns |
| **Workflow** | Automation outcomes needing attention |
| **Approvals** | Leave, offers, workflow approvals |
| **System** | Billing, integration, maintenance |
| **Digest** | Bundled summary (email/in-app daily) |

Filters: Unread · Workspace · Type.

---

## Workspace-specific notifications

| Workspace | Typical events |
|-----------|----------------|
| **Home** | Cross-cutting personal |
| **CRM** | Lead assigned, quote accepted, payment received, overdue invoice |
| **Projects** | Task assigned, mention, risk escalated, health threshold |
| **HR** | Leave request, offer approval, document expiry, announcement |
| **Marketing** | Provider disconnect, sync failure |
| **Operations** | Approval pending, workflow failure, overdue task |
| **Analytics** | Scheduled report ready, threshold alert |
| **Administration** | User invited, role changed, seat limit, security alert |

Badge on workspace switcher = count of unread in that workspace (optional preference).

---

## Priority model

| Level | Examples | UX |
|-------|----------|-----|
| **P0 Critical** | Security, payroll failure, SLA breach | Priority section + optional email immediate |
| **P1 Action** | Approvals, assignments | Inbox + badge |
| **P2 FYI** | Status updates | Inbox; default quieter |
| **P3 Digest** | Low-value batches | Digest only |

Users can demote types in Preferences; cannot demote org-mandatory security.

---

## Channels

| Channel | Use |
|---------|-----|
| In-app bell | Default |
| Email | Approvals, digests, critical |
| (Future) Push / ChatOps | Integrations |

Org defaults in Configuration Hub; personal overrides in User → Preferences ([settings-architecture.md](./settings-architecture.md)).

---

## Mentions, assignments, approvals

| Type | Read behavior | Primary CTA |
|------|---------------|-------------|
| Mention | Marks read on open | Jump to comment |
| Assignment | Read on open | Open record |
| Approval | Read on decision or open | Approve / Reject / View |

Bulk approve only where policy allows.

---

## Workflow & system

- Failed workflow → Operations + Admin  
- Successful silent automations → no notification (activity only)  
- Integration degraded → Admin + Marketing if provider  

---

## Digest

| Setting | Default |
|---------|---------|
| Daily digest time | Org timezone morning |
| Includes | P2 leftovers, counts |
| Skip if empty | Yes |

---

## UX rules

1. Click notification → deep link with workspace switch if needed.  
2. Mark read on view; “Mark all read” available.  
3. Snooze optional (P2).  
4. Do not use notifications for marketing inside product.  
5. Respect `notification-preferences` and org settings.  

---

## Relationship to Activity

| Activity | Notification |
|----------|--------------|
| Broad timeline | Actionable subset |
| Always on entity | May or may not notify |
| Noise collapsed | Priority filtered |

---

## Empty inbox

“You’re all caught up.” + link to adjust preferences.

---

## Anti-patterns

- Notifying on every field edit  
- Duplicate email + in-app without preference  
- Notifications that 403 on click  
- Mixing Platform console alerts into tenant inbox  
