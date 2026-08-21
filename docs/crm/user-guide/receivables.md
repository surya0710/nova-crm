# CRM User Guide - Receivables

## Purpose
Track customer outstanding balances, invoice collection status, aging, and the customer ledger.

## Who should use this feature
Finance and account managers with `invoices.view` or `finance.view`.

## Prerequisites
- Issued invoices
- Optional payments and applied credit/debit notes

## Step-by-step instructions
1. Open **Receivables** for the organization aging board and outstanding invoices.
2. Filter by collection status: unpaid, partial, paid, or overdue.
3. Open a customer profile for the **Account Statement**: invoiced, paid, credits, debits, outstanding, status counts, and aging buckets (Current, 1–30, 31–60, 61–90, 90+).
4. Ledger rows show invoices (debit), payments allocated to invoices (credit), credit notes (credit), and debit notes (debit) with a running balance.
5. Timeline events include payments, issued invoices, and applied adjustments.

## Expected result
Outstanding uses invoice total minus payments minus applied credits plus applied debits. Stored invoice total and amount paid never change when a note is applied.

## Best Practices
Apply credit notes instead of editing issued invoice totals. Review aging weekly.

## Common Mistakes
Treating invoice **Balance due** (total − paid) as the commercial outstanding after credits.

## FAQ
Aging uses due date versus today. Not-yet-due invoices sit in **Current**.
