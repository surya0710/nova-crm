# CRM User Guide - Contacts

## Purpose
Track people at a customer company (HubSpot-style contacts under an account).

## Who should use this feature
Sales users, account managers, and relationship owners with `customers.view` / `customers.update`.

## Prerequisites
- Customer (company) record exists

## Fields
- Name
- Designation / title
- Department
- Email, phone, WhatsApp
- Decision-maker flag
- Primary contact
- Status (active / inactive)
- Timeline notes, tasks, calls, meetings, follow-ups
- Email logging when a company email is sent to this person

## Step-by-step instructions
1. Open the company and choose **Add contact**, or start from **CRM → Contacts**.
2. Enter verified communication details and role.
3. Mark **Primary** for the billing/party contact. Mark **Decision maker** when they influence the deal.
4. Add timeline notes after each interaction.
5. Optionally link the contact on a company support ticket.

## Expected result
Each company has one or more contacts. The primary contact name, email, and phone stay aligned with the customer party fields.

## Best Practices
Keep one primary contact. Prefer company email addresses. Record title and department so routing stays clear.

## Common Mistakes
Unlinked contacts and stale phone/email information.

## FAQ
Contacts use the same `customers.*` permissions as the parent company. They are not a separate RBAC module.
