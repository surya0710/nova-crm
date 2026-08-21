# CRM User Guide - Tickets

## Purpose
Manage customer support tickets from a dedicated CRM workspace on the existing `customer_tickets` records.

## Who should use this feature
Sales, support, and account managers with `customers.view` (list/detail) and `customers.update` (create, assign, notes, lifecycle).

## Prerequisites
- A customer (company) record exists
- Organization members available for assignment

## Fields
- Number, subject, details
- Status: Open → Pending → Resolved → Closed
- Priority: low, medium, high, urgent
- Customer, contact, assignee
- SLA due time (from priority hours, overridable)
- Notes timeline

## Step-by-step instructions
1. Open **CRM → Tickets** for search, filters, sort, and pagination.
2. Filter by customer, assignee, status, priority, or overdue SLA.
3. Create a ticket from a customer record (**New ticket**).
4. Assign or reassign from the ticket page.
5. Move status along Open → Pending → Resolved → Closed. Use **Reopen** to return a resolved or closed ticket to Open.
6. Add notes; the first note records first response time. Ticket notes also appear on the customer timeline.

## Expected result
Open, pending, overdue, and unassigned counts show on the ticket workspace and the CRM tickets dashboard widget.

## Best Practices
Keep priority accurate so SLA due times stay meaningful. Reassign instead of leaving tickets unowned.

## Common Mistakes
Closing a ticket without a resolution note. Skipping reopen and creating a duplicate ticket.

## FAQ
Tickets reuse `customers.*` permissions and stay organization-scoped. They are not a separate RBAC module.
