# Maintenance Procedures

## Planned maintenance
- [ ] Announce window to CS/Support and affected customers
- [ ] `php artisan down` with retry message (if needed)
- [ ] Perform maintenance (patch OS, rotate certs, vacuum/optimize as approved)
- [ ] Smoke tests
- [ ] `php artisan up`
- [ ] Confirm monitoring

## Routine tasks
| Task | Cadence |
|------|---------|
| Dependency/security patches | Monthly |
| Certificate expiry review | Monthly |
| Failed job triage | Daily |
| Backup drill | Monthly |
| Access review (platform admins) | Quarterly |