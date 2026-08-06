# Phase 10.6 — HRMS Mobile API Platform Progress Report

## 1. Phase Summary

**Objective:** Consolidate and standardize a production-ready Mobile API gateway for HRMS at `/api/v1/hrms`, reusing existing domain services as the single source of truth.

**Nature:** API consolidation and standardization — **not** a rebuild.

**Overall implementation status:** **Complete — HRMS Mobile API Platform**

**Depends on:** Phases 10.1 (Foundation), 10.3.1–10.3.7 (Payroll & Tax), 10.5 (Attendance Enterprise).

Existing domain APIs remain untouched for backward compatibility:

| Legacy path | Status |
|---|---|
| `/api/v1/attendance` | Preserved |
| `/api/v1/payroll` | Preserved |
| `/api/v1/tax` | Preserved |
| `/api/v1/recruitment` | Preserved |

---

## 2. Architecture

```
Mobile App
      │
      ▼
API Controllers  (+ Form Requests)
      │
HRMSApiFacadeService  (orchestration only)
      │
Existing Domain Services
  AttendanceService | LeaveService | PayrollService | TaxFacadeService
  EmployeeProfileService | EmployeeDocumentService | RecruitmentApiService
  HrmsDashboardService | EssContext | MobileNotificationInboxService
      │
Models
```

### Hard rules enforced

| Layer | Must not |
|---|---|
| Controllers | Business logic; permission implementation (use middleware + policies) |
| `HRMSApiFacadeService` | Calculations, persistence, SQL, permission checks |
| Auth | Second auth system (Sanctum + `LoginRequest` + Password Broker only) |
| Uploads | Generic upload engine (MIME/size/virus hook → owning domain service) |

---

## 3. API Versioning

- Canonical mobile gateway: **`/api/v1/hrms`**
- Routes: [`routes/api_hrms.php`](../routes/api_hrms.php) required from [`routes/api.php`](../routes/api.php)
- Auth middleware: `auth:sanctum`, `throttle:api`, `set.organization`, `ensure.organization`, `organization.api`
- Organization selection: `X-Organization-Id` header

---

## 4. Standard Response Format

Implemented via [`App\Support\Api\ApiResponse`](../app/Support/Api/ApiResponse.php) and [`App\Support\Api\ApiQuery`](../app/Support/Api/ApiQuery.php).

```json
{
  "success": true,
  "message": "",
  "data": {},
  "meta": {},
  "errors": []
}
```

API exception rendering in [`bootstrap/app.php`](../bootstrap/app.php):

| Condition | Status |
|---|---|
| ValidationException | 422 |
| AuthenticationException | 401 |
| AuthorizationException | 403 |
| ModelNotFound / NotFoundHttpException | 404 |
| MissingEmployeeRecordException | 200 + `empty_state` in data |

---

## 5. Authentication & Device Management

[`MobileAuthService`](../app/Services/Hrms/MobileAuthService.php) wraps existing Sanctum + credential validation patterns from `LoginRequest` + Laravel Password Broker.

| Method | Path | Auth |
|---|---|---|
| POST | `/api/v1/hrms/auth/login` | Public (`throttle:hrms-mobile-auth`) |
| POST | `/api/v1/hrms/auth/refresh` | Public |
| POST | `/api/v1/hrms/auth/forgot-password` | Public |
| POST | `/api/v1/hrms/auth/reset-password` | Public |
| POST | `/api/v1/hrms/auth/logout` | Sanctum |
| POST | `/api/v1/hrms/auth/change-password` | Sanctum |
| POST | `/api/v1/hrms/devices` | Sanctum |
| DELETE | `/api/v1/hrms/devices/{device}` | Sanctum |

### Tokens

- Access ability: `hrms-mobile` (TTL: `HRMS_MOBILE_ACCESS_TTL`, default 60 minutes)
- Refresh ability: `hrms-mobile-refresh` (TTL: `HRMS_MOBILE_REFRESH_TTL_DAYS`, default 30 days)

### Device table `user_devices`

`user_id`, `organization_id`, `employee_id`, `device_uuid`, `device_name`, `platform`, `app_version`, `push_token`, `last_login_at`, `last_seen_at`, `last_ip`, `is_active`, token ids.

Push token is stored only — **no FCM/APNs provider** in this phase.

### Login example

```http
POST /api/v1/hrms/auth/login
Content-Type: application/json

{
  "email": "employee@example.com",
  "password": "secret",
  "device_uuid": "device-abc",
  "device_name": "Pixel 8",
  "platform": "android",
  "app_version": "1.0.0",
  "push_token": "optional-fcm-token"
}
```

```json
{
  "success": true,
  "message": "Logged in successfully.",
  "data": {
    "access_token": "...",
    "refresh_token": "...",
    "access_expires_at": "...",
    "refresh_expires_at": "...",
    "user": { "id": 1, "name": "...", "email": "..." },
    "organizations": [{ "id": 1, "name": "...", "slug": "..." }],
    "employee": { "id": 10, "employee_code": "E001", "...": "..." },
    "device": { "id": 1, "device_uuid": "device-abc", "...": "..." }
  },
  "meta": {},
  "errors": []
}
```

Subsequent requests:

```http
Authorization: Bearer {access_token}
X-Organization-Id: {organization_id}
Accept: application/json
```

---

## 6. Endpoint Catalogue

### ESS (`permission:ess.access`) — `/api/v1/hrms/me`

| Area | Endpoints |
|---|---|
| Dashboard | `GET dashboard` |
| Profile | `GET/PUT/PATCH profile` |
| Attendance | `GET summary|history|calendar`, `POST clock-in|clock-out`, `GET/POST corrections` |
| Leave | `GET balances|types|history`, `POST /`, `DELETE {application}` |
| Payroll | `GET payslips`, `GET payslips/{id}`, `GET payslips/{id}/download`, `GET salary-structure` |
| Tax | `GET dashboard|regimes|projections|declarations|proofs`, related POSTs |
| Documents | `GET/POST /`, `GET {id}`, `GET {id}/download` |
| Notifications | `GET /`, `GET count`, `POST read-all`, `POST {id}/read` |

### Manager (`permission:manager.dashboard`)

| Method | Path |
|---|---|
| GET | `/manager/dashboard` |
| GET | `/manager/attendance` |
| GET | `/manager/leave/pending` |
| POST | `/manager/leave/{application}/approve` (`leave.approve`) |
| POST | `/manager/leave/{application}/reject` (`leave.approve`) |
| GET | `/manager/directory` |

### HR (`permission:hrms.view`)

| Method | Path |
|---|---|
| GET | `/hr/dashboard` |
| GET | `/hr/directory` |
| GET | `/hr/stats` |

### Recruitment (`permission:recruitment.view`)

| Method | Path |
|---|---|
| GET | `/recruitment/jobs`, `/jobs/{job}` |
| GET | `/recruitment/candidates`, `/candidates/{candidate}` |
| GET | `/recruitment/applications`, `/applications/{application}` |
| GET | `/recruitment/offers`, `/offers/{offer}` |

---

## 7. Permission Matrix (high level)

| Surface | Required permission(s) |
|---|---|
| ESS `/me/*` | `ess.access` (+ model policies for own records) |
| Manager | `manager.dashboard` (+ `leave.approve` for approve/reject) |
| HR | `hrms.view` |
| Recruitment | `recruitment.view` |
| Payslip download | `payslip.download` / policy |
| Tax mutations | existing `tax.*` policies where authorized |

No role-name checks in controllers — Dynamic RBAC + policies only.

---

## 8. File Uploads

[`MobileUploadValidator`](../app/Services/Hrms/MobileUploadValidator.php) validates MIME + size and runs [`VirusScanHook`](../app/Contracts/VirusScanHook.php) (default [`NoopVirusScanHook`](../app/Services/Security/NoopVirusScanHook.php)).

Persistence remains in:

- `EmployeeDocumentService` (documents)
- `TaxFacadeService` / `TaxProofService` (tax proofs)
- Other owning services for leave/recruitment/profile as wired

Config: `config('hrms.mobile.uploads.*')`.

---

## 9. API Resources

Under `app/Http/Resources/Hrms/`:

`EmployeeResource`, `AttendanceResource`, `LeaveResource`, `PayrollResource`, `PayslipResource`, `TaxResource`, `DocumentResource`, `RecruitmentResource`, `NotificationResource`, `UserDeviceResource`

Public DTOs only — no engine versions or calculation internals.

---

## 10. Rate Limiting

| Limiter | Scope |
|---|---|
| `hrms-mobile-auth` | 10/min by email+IP (login/forgot/reset/refresh) |
| `api` | 120/min (authenticated HRMS routes) |

---

## 11. Testing

| Suite | Location |
|---|---|
| Unit facade | `tests/Unit/Hrms/HRMSApiFacadeServiceTest.php` |
| Feature auth/devices | `tests/Feature/Hrms/MobileApi/HrmsMobileAuthApiTest.php` |
| Feature ESS/RBAC/tenant/format | `tests/Feature/Hrms/MobileApi/HrmsMobileEssApiTest.php` |

`RefreshDatabase` used in **test environment only**. No `migrate:fresh` against development/production databases.

---

## 12. Mobile Integration Guide

1. Call `POST /api/v1/hrms/auth/login` with credentials (+ optional device fields).
2. Store `access_token` and `refresh_token` securely on device.
3. Send `Authorization: Bearer {access_token}` and `X-Organization-Id` on every scoped request.
4. On 401, call `POST /api/v1/hrms/auth/refresh` with `refresh_token`; if refresh fails, return to login.
5. Register/update push token via `POST /api/v1/hrms/devices` (provider wiring is a future phase).
6. Prefer `/api/v1/hrms/*` for new mobile clients; legacy `/api/v1/{attendance,payroll,tax,recruitment}` remain available.

---

## 13. Work Packages Delivered

| # | Package | Status |
|---|---|---|
| 1 | Unified HRMS API Gateway | Done |
| 2 | API Standards (ApiResponse/ApiQuery/exceptions) | Done |
| 3 | Mobile Authentication + Devices | Done |
| 4 | HRMSApiFacadeService | Done |
| 5 | ESS APIs | Done |
| 6 | Manager APIs | Done |
| 7 | HR/Admin APIs | Done |
| 8 | Recruitment under `/hrms/recruitment` | Done |
| 9 | File uploads (delegated) | Done |
| 10 | API Resources | Done |
| 11 | Security (RBAC/tenant/policies) | Done |
| 12 | Performance hardening (pagination/eager loads/rate limits) | Done |
| 13 | Documentation | Done |
| 14 | Testing | Done |

---

## 14. Non-Goals (deferred)

Native apps, Flutter/RN shells, offline sync, push providers, chat, WebSockets, AI assistant, biometric/hardware attendance, face recognition, geo-fencing.
