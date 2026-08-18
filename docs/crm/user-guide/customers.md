# CRM User Guide - Customers

## Purpose
Maintain customer records, GST tax profile, and commercial activity.

## Who should use this feature
Sales teams, account managers, and support operations.

## Prerequisites
- Valid customer data standards
- Access to customer management actions

## Step-by-step instructions
1. Create the customer with billing address (existing address fields).
2. Optional GST profile: GSTIN (checksum validated), PAN (auto-filled from GSTIN), registration type, billing state, place of supply, exemption, default tax preference, shipping address.
3. Link opportunities, quotations, and invoices.
4. Review the **Activity** timeline for notes plus quotation/invoice events (created, sent, accepted/rejected, converted, issued, status changes).
5. Email the customer from the profile when organization SMTP is configured.

## Expected result
Accurate customer records support sales, tax determination, and service workflows.

## Best Practices
Keep GSTIN/PAN consistent and ownership current. Use shipping-same-as-billing unless delivery differs.

## Common Mistakes
Duplicate records, invalid GSTIN checksum, and PAN that does not match the GSTIN.

## FAQ
GST fields are optional. Existing customers without GST data continue to work; tax falls back to simple line tax rates when state codes are missing.
