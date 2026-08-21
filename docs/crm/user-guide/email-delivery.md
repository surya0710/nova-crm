# Delivery Tracking

Each outbound message is a `crm_email_messages` row with status:

`queued` → `sending` → `sent` → (`delivered` | `failed` | `bounced`)

## Provider rules

- SMTP, Gmail, Outlook, and log stop at **Sent** or **Failed**. The app does not invent Delivered.
- SendGrid and Mailgun can advance to **Delivered** / **Bounced** when webhooks are configured (`config/organization_mail.php` `delivery_tracking`).

Headers include Message-ID, In-Reply-To, References, `X-Konnect-Email-Id`, and `X-Konnect-Organization-Id`.

The Communications Center and `/api/v1/crm/email/messages` expose status timestamps, bounce type, and error text — never SMTP passwords or webhook secrets.
