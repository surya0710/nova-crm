# Security Overview

## Tenancy
- Organization-scoped data access (tenant context)
- Separate platform operator console (`/platform`) with distinct auth guard

## Access control
- Role-based permissions (dynamic RBAC)
- Least-privilege role templates for common jobs

## Application security practices
- Production: `APP_DEBUG=false`, HTTPS, secure session cookies
- Forward-only database migrations
- Audit-friendly support processes (no shared customer passwords)

## Data protection
- Backups of database and `storage/app`
- Encryption in transit (TLS)
- Secrets stored in environment / vault — not in git

## Customer responsibilities
- Protect admin credentials
- Assign roles carefully
- Notify NovaCRM of suspected compromise promptly

## More detail
Ask AE for DPA / security questionnaire responses as maintained by Legal/Security.