# Rollback Process

1. Declare incident if customer-impacting
2. `php artisan down` if required
3. Redeploy previous known-good artifact
4. Restore database **only** with approval when migrations are irreversible
5. Clear/rebuild caches as appropriate
6. `php artisan queue:restart`
7. `php artisan up`
8. Smoke test + monitoring
9. RCA

See Technical Operations SOP §13.