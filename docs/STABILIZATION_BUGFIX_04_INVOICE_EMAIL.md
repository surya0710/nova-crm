# STABILIZATION BUGFIX 04 — Invoice Email Delivery Failure

## Summary

Invoice emails failed at send time with a Laravel `Envelope` type error. The root cause was passing an **array** of `Address` objects to the `from` parameter, which Laravel 12 requires to be a single `Address`, `string`, or `null`.

## Root Cause

`InvoiceMail::envelope()` delegated the `from` address to `UsesOrganizationMailFrom::organizationFrom()`, which returns `array<int, Address>`:

```php
protected function organizationFrom(Organization $organization): array
{
    $from = app(OrganizationMailConfig::class)->for($organization)->fromAddress();

    return $from ? [$from] : [];
}
```

That return value was passed directly to `Envelope`:

```php
return new Envelope(
    from: $this->organizationFrom($this->organization), // array — invalid
    ...
);
```

Laravel 12's `Illuminate\Mail\Mailables\Envelope::__construct()` signature:

```php
public function __construct(Address|string|null $from = null, ...)
```

Passing `[$address]` triggers:

```
Argument #1 ($from) must be of type Illuminate\Mail\Mailables\Address|string|null, array given
```

### Why tests did not catch this earlier

Existing feature tests used `Mail::fake()`, which records mailables without fully rendering the Symfony message. The `envelope()` method is only evaluated during real delivery (log, SMTP, array transport), so the type error surfaced in production/manual QA but not in faked tests.

## Fix

`InvoiceMail` now resolves the organization from address as a single `Address` via `OrganizationMailConfig::fromAddress()`:

```php
return new Envelope(
    from: app(OrganizationMailConfig::class)->for($this->organization)->fromAddress(),
    subject: $subject,
    replyTo: ...
);
```

The `UsesOrganizationMailFrom` trait was removed from `InvoiceMail` only. The trait itself was **not** changed (other mailables still carry the same defect; see regression impact).

## Files Modified

| File | Change |
|------|--------|
| `app/Mail/InvoiceMail.php` | Pass `Address\|null` to `Envelope::from`; use `OrganizationMailConfig` directly |
| `tests/Feature/InvoiceMailTest.php` | New feature tests for envelope, attachments, delivery, and error cases |
| `docs/STABILIZATION_BUGFIX_04_INVOICE_EMAIL.md` | This document |

## Laravel Compatibility Notes

- **Laravel version:** 12.x Mailable API
- **`Envelope::from`:** Must be `Address`, `string`, or `null` — not an array
- **`Envelope::replyTo`:** Accepts `array<int, Address|string>` (unchanged; already correct)
- **`Content`:** Markdown template `emails.invoices.sent` — no changes required
- **`Attachment`:** User-uploaded files via `AttachesUploadedFiles` trait — no changes required
- **Queue:** `InvoiceMail` uses `Queueable` + `SerializesModels` but does not implement `ShouldQueue`; sends are synchronous unless explicitly queued elsewhere

## Mail Flow Reviewed

```
InvoiceController::sendMail()
  → OrganizationMailer::isConfigured()     [guard: org mail settings]
  → OrganizationMailer::send()
      → OrganizationMailConfig::registerMailer()   [dynamic smtp/log mailer]
      → Mail::mailer($name)->to($recipient)->send(InvoiceMail)
          → InvoiceMail::envelope()        [FIX: Address|null for from]
          → InvoiceMail::content()         [markdown body with line items]
          → InvoiceMail::attachments()     [optional user-uploaded PDFs]
  → InvoiceService::markIssuedAfterEmail()
```

### Configuration inspected

| Setting | Location | Notes |
|---------|----------|-------|
| `MAIL_MAILER` | `.env` / `config/mail.php` | Default `log`; org emails use per-org dynamic mailer |
| `MAIL_FROM_*` | `config/mail.php` | Global fallback; invoice emails use org `settings.mail.from_*` |
| Org SMTP | `OrganizationMailConfig` | Host, port, encryption, credentials from org settings |

Invoice client emails are sent from the **organization mail account**, not global `.env` settings (by design).

### Attachment behaviour

- Invoice email body renders invoice line items inline (Markdown).
- Optional **user-uploaded** PDF attachments are supported via the send form (`AttachesUploadedFiles`).
- There is **no auto-generated invoice PDF** attachment in the current architecture; "PDF attachment" in QA refers to user-supplied files or the rendered email body.

## Regression Impact

The same `organizationFrom()` array bug exists in these mailables (not fixed in this bugfix):

| Mailable | Affected | Notes |
|----------|----------|-------|
| `QuotationMail` | **Yes** | Scheduled for Bugfix 05 |
| `PaymentMail` | **Yes** | Same trait usage |
| `CustomerMail` | **Yes** | Same trait usage |
| `TestOrganizationMail` | **Yes** | Org test-email feature |
| Password reset | **No** | Uses Laravel's built-in notification |
| Welcome / invitation | **No** | Not present in codebase |

Global `config('mail.from')` is **not** passed to `Envelope` anywhere; the defect is isolated to `UsesOrganizationMailFrom`.

## Tests Executed

```bash
php artisan test --filter=Invoice
php artisan test --filter=Mail
php artisan test
```

### New coverage (`tests/Feature/InvoiceMailTest.php`)

| Area | Tests |
|------|-------|
| Envelope | `from` is `Address`, subject with/without title, reply-to |
| Attachments | PDF filename, MIME type, empty when none uploaded |
| Delivery | Log mailer (real send), array sync driver, SMTP org config |
| Queue | Serialize/unserialize round-trip |
| Errors | Missing recipient, invalid recipient, unconfigured org mail, missing from address |

## Manual Verification

Recommended QA path:

1. Configure organization email (Settings → Email) with Mailtrap or log driver.
2. Create a draft invoice with line items.
3. Preview invoice on show page.
4. Send email to a test recipient.
5. Confirm email received with correct subject, from address, and reply-to.
6. If attaching a PDF via the send form, confirm attachment opens correctly.

**Mailtrap / log mailer:** Verify message appears in inbox or `storage/logs/laravel.log`.

## Deployment Notes

- No migrations required.
- No config changes required.
- Deploy `app/Mail/InvoiceMail.php` and run test suite.
- After deploy, send one test invoice email per organization that uses client email.
- Bugfix 05 should apply the same `Envelope::from` correction to `QuotationMail` (and consider fixing `UsesOrganizationMailFrom` trait once for all org mailables).
