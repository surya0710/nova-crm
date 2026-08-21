# CRM User Guide - Customer Portal Billing

## Purpose
Give linked customers a commercial view of their account: quotes, orders, invoices, payments, notes, and outstanding balance.

## Who should use this feature
Client portal users (`auth:client`) whose login is linked to a customer in the same organization.

## Prerequisites
- Portal login invited against a customer
- Commercial documents belonging to that customer
- Optional: organization **Commercial Automation** payment gateway (`test` records payment immediately)

## Step-by-step instructions
1. Open **Billing** from the portal header.
2. View company documents: quotations (non-draft), sales orders, invoices, payments, credit/debit notes.
3. Download PDFs. Accept or reject quotations that are in **sent** status.
4. Review outstanding balance from the customer ledger (payments and applied notes included).
5. If a payment gateway is configured, pay an outstanding issued invoice. Portal payments are recorded by an organization user (invoice creator or first member), never by the client login.

## Expected result
Customers only see their own organization’s documents for their linked customer. Other customers return 404.

## Best Practices
Send quotations before expecting portal accept/reject. Link every portal user to the correct customer.

## Common Mistakes
Expecting draft quotes/invoices in the portal, or paying when no gateway is configured.

## FAQ
Isolation is organization_id + customer_id on every commercial record.
