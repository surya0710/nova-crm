# Email Webhook Setup

Used only for SendGrid and Mailgun delivery events.

## Endpoint

`POST /webhooks/email/{provider}/{token}`

- CSRF-exempt, rate-limited (`email-webhooks`, 120/min)
- Token belongs to one organization
- Message lookup is scoped to that `organization_id`
- Duplicate `provider_event_id` rows are ignored
- SendGrid: HMAC of body **or** `Authorization: Bearer {signing_secret}`
- Mailgun: HMAC signature headers

Saving organization mail with a tracking provider creates the endpoint. Copy the URL and signing secret from **Settings → Email**. Rotate the secret in the same screen.

Unsigned or cross-organization events are rejected (401) or ignored.
