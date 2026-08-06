# Deployment Guide (Customer / Partner Facing)

This guide summarizes how Konnect Nex is deployed. Internal operators should prefer [overview.md](overview.md) and [../operations/production-deployment-checklist.md](../operations/production-deployment-checklist.md).

## Requirements
PHP 8.2+ · Composer 2 · Node 18+ · MySQL 8+ · Queue worker · Cron scheduler · HTTPS

## High-level steps
1. Configure environment (`.env`)
2. Install PHP dependencies
3. Build front-end assets
4. Run forward migrations only
5. Cache config/routes/views
6. Start queue workers and scheduler
7. Verify `GET /up`

## Safety
Never wipe production databases. Use backups before upgrades.

## Related
[UPGRADE.md](../../UPGRADE.md) · [Troubleshooting](../troubleshooting/overview.md)