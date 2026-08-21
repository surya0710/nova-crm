# CRM User Guide - Customers

## Purpose
Maintain company (account) records, GST tax profile, contacts, and commercial activity.

## Who should use this feature
Sales teams, account managers, and support operations.

## Prerequisites
- Valid customer data standards
- Access to customer management actions (`customers.view` / `customers.create` / `customers.update`)

## Company record
The customer is the **company**. Capture:

- Lifecycle stage (subscriber → evangelist)
- Type (individual / company)
- Segment and source
- Status and tags
- Owner / salesperson (`assigned_to`)
- Notes, documents, and an activity timeline
- Value summary (open pipeline, won value, invoiced, outstanding)

## Contacts
Add multiple people under the company (name, title, department, email, phone, WhatsApp, primary, decision maker, status). The primary contact keeps the company’s party name, email, and phone in sync. See [contacts.md](./contacts.md).

## Relationships
The company profile lists:

```text
Company
 ├── Contacts
 ├── Opportunities
 ├── Quotations
 ├── Sales Orders
 ├── Invoices
 ├── Payments
 ├── Tickets
 └── Activities
```

## Step-by-step instructions
1. Create the customer with billing address (existing address fields).
2. Optional GST profile: GSTIN (checksum validated), PAN (auto-filled from GSTIN), registration type, billing state, place of supply, exemption, default tax preference, shipping address.
3. Set lifecycle, type, segment, source, owner, and tags.
4. Add contacts. Mark one primary contact and decision makers.
5. Link opportunities, quotations, sales orders, invoices, payments, and tickets.
6. Review the **Account Statement** for outstanding, aging, and ledger rows (invoices, payments, credit/debit notes).
7. Review the **Activity** timeline for notes plus commercial and portal events.
8. Email the customer from the profile when organization SMTP is configured.

## Expected result
Accurate customer records support sales, tax determination, and service workflows.

## Best Practices
Keep GSTIN/PAN consistent and ownership current. Use shipping-same-as-billing unless delivery differs. Keep one primary contact.

## Common Mistakes
Duplicate records, invalid GSTIN checksum, and PAN that does not match the GSTIN.

## FAQ
GST fields are optional. Existing customers without GST data continue to work; tax falls back to simple line tax rates when state codes are missing. Creating a customer also seeds a primary contact from the party name.
