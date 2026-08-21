# CRM User Guide - Lifecycle Automation

## Purpose
Advance customer lifecycle stage from commercial milestones using the existing Workflow engine, without a parallel automation stack.

## Who should use this feature
Revenue operations and administrators who configure workflows (`workflows` module).

## Prerequisites
- Workflows enabled for the organization
- Customers with lifecycle stages configured

## Milestones
Built-in stage advances (never regress, recorded once per customer):

| Milestone | Target stage |
|-----------|----------------|
| Opportunity created | Opportunity |
| Quotation accepted | Opportunity |
| Opportunity won | Customer |
| Sales order confirmed | Customer |
| First issued invoice | Customer |
| First payment | Evangelist |

Customer creation is recorded as a milestone and keeps the stage set at create time.

## Step-by-step instructions
1. Open **Workflows** and choose a trigger: `customer.created`, `opportunity.created`, `opportunity.won`, `opportunity.lost`, `quotation.accepted`, `sales_order.confirmed`, `customer.first_invoice`, `customer.first_payment`, or ticket triggers.
2. Add existing actions: notify user, create task, create activity, add note, assign owner, or **Change customer lifecycle stage**.
3. Save and activate. Duplicate runs are skipped by workflow execution idempotency keys and by the lifecycle milestone unique constraint.

## Expected result
The customer lifecycle moves forward once per milestone. The same events can still notify, create tasks, or email via existing workflow actions and commercial automation.

## Best Practices
Use **Change customer lifecycle stage** only when the built-in mapping is not enough. Keep notify/task actions on the commercial triggers.

## Common Mistakes
Building a second automation engine. Expecting lifecycle to move backward.

## FAQ
Emails for quotations and invoices stay in commercial automation. Workflows send in-app notifications (`notify_user`) and can add activity/audit entries.
