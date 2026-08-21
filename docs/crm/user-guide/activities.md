# CRM User Guide - Sales Activities

## Purpose
Log and review sales work across customers, contacts, and opportunities from one activity stream.

## Who should use this feature
Anyone with `customers.view`, `leads.view`, or `opportunities.view`. Creating or completing activities uses `customers.update` (or opportunity update when logging on a deal).

## Prerequisites
- Related customer, contact, or opportunity exists

## Activity types
Tasks, calls, meetings, follow-ups, notes, and emails.

## Step-by-step instructions
1. Open **CRM → Activities**.
2. Switch **My activities**, **Upcoming**, **Overdue**, **Completed**, or **All**.
3. Filter by type. Complete open items from the list.
4. Log activities on a contact, customer, or opportunity record (subject, due date, assignee, priority).
5. Follow-ups and tasks stay open until completed; calls, notes, and emails complete when logged unless marked open.

## Expected result
Open work with due dates rolls up to the opportunity **Next activity** field and the customer timeline.

## Best Practices
Assign every follow-up. Use overdue view at the start of the day.

## Common Mistakes
Logging a follow-up without a due date. Completing work only in notes and leaving the activity open.

## FAQ
Lead follow-ups remain on the same page when you have `leads.view`. Sales activities reuse `customers.*` rather than a new permission set.
