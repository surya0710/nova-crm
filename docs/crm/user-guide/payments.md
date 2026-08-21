# CRM User Guide - Payments

## Purpose
Record customer payments against invoices, allocate partial and multiple payments, and issue PDF/email receipts.

## Who should use this feature
Finance teams and authorized billing operators with `payments.view` / `payments.create`.

## Prerequisites
- Issued (or partially paid) invoice
- Payment method and, for bank transfer or cheque, optional bank/account details

## Step-by-step instructions
1. Open the invoice and start payment entry, or use **Record Payment**.
2. Enter amount, date, method, reference/transaction number, and notes.
3. Add bank name, account name, account number, and IFSC/routing when applicable.
4. Save. The invoice payment status becomes **Unpaid**, **Partial**, **Paid**, or **Overpaid**.
5. Download a PDF receipt or email it. The authenticated sender is CC’d; extra CC recipients are de-duplicated. Email includes the PDF receipt.

## Expected result
Payments are immutable, allocated to one invoice, and visible on the customer timeline.

## Best Practices
Capture payment references and reconcile daily. Overpayments are allowed and marked **Overpaid**.

## Common Mistakes
Recording against a draft or cancelled invoice, or omitting the transaction reference.

## FAQ
Multiple payments can be recorded against the same invoice. Payments cannot be edited or deleted after recording.
