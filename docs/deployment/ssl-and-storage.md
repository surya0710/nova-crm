# SSL & Storage

## SSL
- Terminate TLS at load balancer / reverse proxy
- Set `APP_URL` to HTTPS canonical URL
- `SESSION_SECURE_COOKIE=true`
- Monitor certificate expiry (ops checklist)

## Storage
- App writes under `storage/`
- Run `php artisan storage:link` for public disk
- Include `storage/app` in backup set
- Ensure web user permissions on `storage` and `bootstrap/cache`