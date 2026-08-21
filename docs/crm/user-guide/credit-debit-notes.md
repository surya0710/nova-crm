# CRM User Guide - Credit and Debit Notes

## Purpose
Issue commercial adjustments against invoices without rewriting historical invoice values.

## Who should use this feature
Finance users with `adjustment_notes.view` / `create` / `update`.

## Prerequisites
- Issued (non-draft, non-cancelled) invoice for apply
- Customer and line items (product or free text)

## Step-by-step instructions
1. From the invoice, start a **credit note** (reduces amount owed) or **debit note** (increases amount owed), or create one from the CRM menu.
2. Add reason, line items, tax, and optional notes/terms. Save as draft.
3. Issue, download PDF, or email. The authenticated sender is CC’d; extra CC recipients are de-duplicated.
4. **Apply** against the linked invoice. Applied amount is stored on the note. Invoice `total` and `amount_paid` stay unchanged.
5. Customer ledger, receivables outstanding, and timeline pick up issued/applied notes.

## Expected result
Adjustments are auditable documents with their own numbers, tax snapshot, and PDF.

## Best Practices
Always link a note to the invoice you are correcting. Do not edit issued invoice lines to “fix” pricing.

## Common Mistakes
Applying a draft note, or expecting the invoice stored total to change after apply.

## FAQ
Cancel is allowed on draft or issued notes that are not yet applied.
