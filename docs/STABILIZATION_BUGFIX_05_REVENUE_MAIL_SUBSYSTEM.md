# STABILIZATION BUGFIX 05 — Revenue Mail Subsystem Compatibility

## Summary

Bugfix 04 corrected `InvoiceMail` locally, but the same Laravel 12 `Envelope::from` type mismatch existed across all organization-scoped Revenue mailables via a shared trait. This bugfix applies one architectural correction at the trait layer so every Revenue mailable resolves `from` as a single `Address|null`.

## Root Cause

`UsesOrganizationMailFrom::organizationFrom()` wrapped the result of `OrganizationMailConfig::fromAddress()` in an array:

```php
return $from ? [$from] : [];
```

Every Revenue mailable passed that array to `Envelope::from`:

```php
return new Envelope(
    from: $this->organizationFrom($this->organization), // array — invalid in Laravel 12
    ...
);
```

Laravel 12 requires:

```php
Envelope::__construct(Address|string|null $from = null, ...)
```

## Shared Architectural Fix

Corrected `UsesOrganizationMailFrom` to delegate directly to `OrganizationMailConfig` and return `?Address`:

```php
protected function organizationFrom(Organization $organization): ?Address
{
    return app(OrganizationMailConfig::class)->for($organization)->fromAddress();
}
```

**Ownership remains:**

```
OrganizationMailConfig::fromAddress()
        ↓
UsesOrganizationMailFrom::organizationFrom()   [thin delegate]
        ↓
Revenue Mailables::envelope()
```

The trait stays as a convenience wrapper so mailables do not duplicate `OrganizationMailConfig` resolution. It no longer transforms the return type.

`InvoiceMail` was realigned to use the shared trait (Bugfix 04 had inlined the config call as a local workaround).

## Files Modified

| File | Change |
|------|--------|
| `app/Mail/Concerns/UsesOrganizationMailFrom.php` | Return `?Address` instead of `array<Address>` |
| `app/Mail/InvoiceMail.php` | Restored trait usage for consistent architecture |
| `tests/Feature/RevenueMailTest.php` | New feature tests for Quotation, Payment, Customer, TestOrganization mail |
| `docs/STABILIZATION_BUGFIX_05_REVENUE_MAIL_SUBSYSTEM.md` | This document |

**Unchanged mailable classes** (already used the trait correctly; fixed by trait change):

- `app/Mail/QuotationMail.php`
- `app/Mail/PaymentMail.php`
- `app/Mail/CustomerMail.php`
- `app/Mail/TestOrganizationMail.php`

## Laravel Compatibility Notes

| API | Requirement | Status |
|-----|-------------|--------|
| `Envelope::from` | `Address\|string\|null` | Fixed |
| `Envelope::replyTo` | `array<Address\|string>` | Already correct |
| `Content::markdown` | Template path | Unchanged |
| `Attachment::fromPath` | User uploads via `AttachesUploadedFiles` | Unchanged |
| Queue | `Queueable` + `SerializesModels` | Verified via serialization tests |

No deprecated Laravel mail APIs are used.

## Revenue Mail Flows Reviewed

| Flow | Mailable | From | Reply-To | Attachments | Mailer |
|------|----------|------|----------|-------------|--------|
| Quotation send | `QuotationMail` | Org config | Org email / creator | Optional user PDFs | Dynamic org mailer |
| Invoice send | `InvoiceMail` | Org config | Org email / creator | Optional user PDFs | Dynamic org mailer |
| Payment receipt | `PaymentMail` | Org config | Org email / recorder | Optional user PDFs | Dynamic org mailer |
| Customer message | `CustomerMail` | Org config | Org email | Optional user PDFs | Dynamic org mailer |
| Org test email | `TestOrganizationMail` | Org config | — | None | Dynamic org mailer |

All flows route through `OrganizationMailer::send()` → `OrganizationMailConfig::registerMailer()` → `Mail::mailer($name)->send()`.

## Regression Review

| Area | Impact |
|------|--------|
| Password reset | **None** — Laravel notification, not org mailable |
| Email verification | **None** — Laravel notification |
| Laravel Notifications | **None** |
| Non-Revenue mailables | **None** — only org-scoped Revenue mailables use the trait |

## Tests Executed

```bash
php artisan test --filter=Mail
php artisan test --filter=Quotation
php artisan test --filter=Payment
php artisan test
```

### New coverage (`tests/Feature/RevenueMailTest.php`)

| Area | Tests |
|------|-------|
| Quotation | Envelope `from`, PDF attachment, log send, SMTP config, queue serialization |
| Payment | Envelope `from`, queue serialization |
| Customer | Envelope `from`, subject, reply-to |
| TestOrganizationMail | Envelope `from`, SMTP config |
| Shared | Config as single source of truth, missing from address, unconfigured org, invalid SMTP, password reset unaffected |

Existing `InvoiceMailTest` and `OrganizationMailTest` continue to cover invoice-specific and settings UI flows.

## Manual QA

For each flow, configure organization email (Settings → Email) and verify end-to-end delivery:

1. **Quotation** — Send with optional PDF attachment; confirm from address and reply-to.
2. **Invoice** — Send; confirm subject, from, and optional attachment.
3. **Payment** — Email receipt; confirm from and subject.
4. **Customer** — Send with subject and attachments; confirm from and reply-to.
5. **Organization test mail** — Send test from Settings → Email; confirm delivery.

Use Mailtrap, log mailer, or SMTP as available in the target environment.

## Deployment Notes

- No migrations or config changes required.
- Deploy `app/Mail/Concerns/UsesOrganizationMailFrom.php` and `app/Mail/InvoiceMail.php`.
- Run the full PHPUnit suite after deploy.
- Smoke-test one send per Revenue mail type per organization using client email.
