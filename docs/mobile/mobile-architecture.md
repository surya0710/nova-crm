# Mobile Architecture

Recommended architecture for the Konnect Nex HRMS mobile application. **This section describes mobile client design patterns** — the Laravel backend does not ship a mobile app.

---

## Technology Stack

| Layer | Recommendation |
|-------|----------------|
| Framework | **Flutter** (preferred) or React Native |
| Min OS | iOS 15+, Android 8+ (API 26+) |
| HTTP | `dio` (Flutter) with interceptors |
| State | Riverpod or Bloc |
| Secure storage | `flutter_secure_storage` for tokens |
| Local cache | Hive or SQLite via `drift` |
| Push (future) | Firebase Cloud Messaging |

---

## Flutter Architecture

```
lib/
├── main.dart
├── app/
│   ├── app.dart                 # MaterialApp, routing
│   └── env.dart                 # Environment config
├── core/
│   ├── api/
│   │   ├── api_client.dart      # Dio instance, interceptors
│   │   ├── api_exception.dart
│   │   └── endpoints.dart
│   ├── auth/
│   │   ├── auth_repository.dart
│   │   └── token_storage.dart
│   ├── organization/
│   │   └── org_context.dart     # Selected org ID
│   ├── models/                  # JSON serializable DTOs
│   └── utils/
├── features/
│   ├── attendance/
│   │   ├── data/
│   │   ├── domain/
│   │   └── presentation/
│   ├── dashboard/
│   ├── leave/                   # Future — when API exists
│   └── profile/
└── shared/
    ├── widgets/
    └── theme/
```

### Layer Responsibilities

| Layer | Responsibility |
|-------|----------------|
| **Presentation** | Screens, widgets, state notifiers |
| **Domain** | Use cases, business rules (client-side validation mirrors server) |
| **Data** | API calls, DTO parsing, local cache |

---

## State Management

Use **Riverpod** (recommended) or **Bloc**:

- `AuthNotifier` — token presence, user session
- `OrganizationNotifier` — selected `organization_id`
- `AttendanceNotifier` — dashboard state, optimistic check-in/out
- `PermissionsNotifier` — cached from `/rbac/authorization`

Refresh attendance state from the `dashboard` object returned by check-in/out responses to avoid extra round-trips.

---

## API Layer

### Base Configuration

```dart
// env.dart
class Env {
  static const baseUrl = String.fromEnvironment('API_BASE_URL',
      defaultValue: 'https://your-nova-crm.host');
}
```

### Interceptors

1. **Auth** — attach `Authorization: Bearer {token}`
2. **Organization** — attach `X-Organization-Id: {id}`
3. **Error** — map 401 → logout, 403 → permission denied UI, 422 → field errors
4. **Logging** — debug-only request/response logging

### Endpoint Constants

Align with backend routes in `routes/api*.php`:

```dart
class Endpoints {
  static const attendanceDashboard = '/api/v1/attendance/dashboard';
  static const attendanceCheckIn = '/api/v1/attendance/check-in';
  static const attendanceCheckOut = '/api/v1/attendance/check-out';
  static const lookups = '/api/v1/lookups';
  static const dashboardWidgets = '/api/v1/dashboard/widgets';
}
```

---

## Authentication

**Current backend limitation:** No mobile login API. Options:

1. **Phase 1 (current):** User creates a Personal Access Token in web Settings → API Tokens (`api.tokens` permission), pastes into mobile app during setup.
2. **Phase 2 (future):** Backend adds `POST /api/v1/auth/login` returning Sanctum token.

Store token in **flutter_secure_storage**. Never persist in SharedPreferences plain text.

Token configuration (`config/sanctum.php`):

- `expiration`: `null` (no global expiry)
- Org setting `api_token_expiry_days` (default 365) exists but is **not enforced** on token creation

---

## Secure Storage

| Key | Content |
|-----|---------|
| `auth_token` | Sanctum bearer token |
| `organization_id` | Selected tenant ID |
| `user_id` | Cached user ID (optional) |

Clear all keys on logout.

---

## Offline Cache

### Cacheable (read-only, stale-while-revalidate)

| Data | Strategy | TTL |
|------|----------|-----|
| Attendance dashboard | Cache last response | 5 min |
| Leave balance widget | Cache | 1 hour |
| Shift info | Cache with dashboard | 5 min |
| Lookup results | Cache per query | 10 min |
| Permissions | Cache | 24 hours |

### Online-only (no offline queue)

| Operation | Reason |
|-----------|--------|
| Check-in / check-out | Server validates leave, holiday, weekend, duplicates |
| Leave application | Balance validation server-side |
| Task updates | Conflict resolution requires server |

Show offline banner when connectivity lost; display cached read data with "last updated" timestamp.

---

## Push Notifications

**Not implemented in backend.** When available, expect:

- `POST /api/v1/devices/register` with `fcm_token`, `platform`
- Payload structure mirroring notification `data` fields: `title`, `body`, `url`, `category`, `organization_id`

Until then, poll `GET /dashboard/widgets/notifications/data` on app resume (not ideal for production).

---

## Error Handling

Map HTTP status to user-facing messages:

| Status | Client action |
|--------|---------------|
| 401 | Clear token, navigate to login/setup |
| 403 | Show permission denied |
| 404 | Resource not found |
| 422 | Display validation errors per field |
| 429 | Retry with backoff (rate limit: 120/min) |
| 200 + `empty_state: true` | Show "no employee record linked" UI |

Handle `MissingEmployeeRecordException` soft empty state (HTTP 200, not 403).

---

## Logging

- Debug builds: log API requests (redact `Authorization` header)
- Production: crash reporting (Firebase Crashlytics / Sentry)
- Never log tokens or PII in production logs

---

## Environment Configuration

| Variable | Description |
|----------|-------------|
| `API_BASE_URL` | Konnect Nex host |
| `API_TIMEOUT_MS` | Request timeout (default 30000) |
| `ENABLE_API_LOGGING` | Debug flag |

Use flavors: `dev`, `staging`, `production`.

---

## Integration Checklist

- [ ] Obtain Sanctum token (web UI or future login API)
- [ ] Store token securely
- [ ] Send `X-Organization-Id` on every request
- [ ] Verify user has `ess.access` permission
- [ ] Verify organization has `hrms` module licensed
- [ ] Use API check-in/out endpoints (ignore web URLs in `actions`)
- [ ] Handle missing employee record empty state
- [ ] Plan for missing leave/profile APIs (web fallback or wait for backend)
