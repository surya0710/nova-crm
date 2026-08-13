# Phase — WFH & Location Policy Progress

## 1. Summary

**Objective:** Organization-scoped Work From Home policy integrated with attendance verification (geofence bypass), RBAC, leave-style approval, audit, tenant isolation, workflow automation, and multi-day requests.

**Status:** ✅ **Complete** (WP1–WP21), including follow-ups WP13–WP21. Verified 2026-08-12.

---

## 2. Work packages

| WP | Scope | Status |
|---|---|---|
| WP1–WP5 | Core policy, permanent/daily/selected_days, approval | ✅ |
| WP6–WP12 | Audit, UI, security, edge cases, attendance, regression, docs | ✅ |
| WP13 | Leave conflict handling | ✅ |
| WP14 | ESS cancel of approved WFH | ✅ |
| WP15 | Workflow automation (existing HRMS engine) | ✅ |
| WP16 | Employee transfer handling | ✅ |
| WP17 | Multi-day WFH requests | ✅ |
| WP18 | Management UX enhancements | ✅ |
| WP19 | Optional map/location | ✅ Deferred (not required) |
| WP20 | Final targeted regression | ✅ |
| WP21 | Documentation | ✅ |

---

## 3. Data model

**Migrations:**

- `2026_08_12_000100_create_wfh_policy_tables.php`
- `2026_08_12_000101_sync_wfh_permissions.php`
- `2026_08_12_000200_add_wfh_request_date_range.php` (`start_date` / `end_date`, backfilled from `work_date`)

| Table | Purpose |
|---|---|
| `employee_wfh_assignments` | Permanent / selected_days eligibility with effective dates |
| `wfh_requests` | Daily **or multi-day** requests (`work_date` + `start_date`/`end_date`) |
| `wfh_approval_steps` | Manager then optional HR steps |

**Org settings key:** `Organization.settings.wfh_policies`

```php
[
  'enabled' => bool,
  'default_policy_type' => 'none|permanent|daily|selected_days',
  'requires_approval' => bool,
  'requires_hr_approval' => bool,
  'bypass_geofence' => bool,
  'record_gps_when_wfh' => bool,
  'allowed_weekdays' => [1..7],
  'cancellation_cutoff_days' => int,
]
```

**Config:** `hrms.wfh_max_request_days` (default 31).

---

## 4. Resolution precedence

For a given employee + date:

1. If **approved leave** covers the date → **not WFH** (`suppressed_by_leave=true`; leave wins calendar + geofence exemption)
2. Approved **daily/multi-day** request covering the date
3. Active **permanent** assignment
4. Active **selected_days** assignment matching ISO weekday
5. Otherwise **none**

Org `enabled=false` short-circuits to not WFH.

---

## 5. Leave conflict behavior (WP13)

- **Submit blocked** when approved leave overlaps the requested range (`LeaveService::getApprovedLeaveForDateRange`).
- **Approve re-checks** leave overlap before final activation.
- **Resolution suppressed** on leave days so WFH geofence exemption does not apply.
- **Calendar** still prefers leave visual over WFH.

---

## 6. Cancellation (WP14)

- HR cancel: `hrms.wfh.requests.cancel` (`wfh.manage` or policy).
- ESS cancel approved: `ess.wfh.cancel` — authorized when owner + `status=approved` + cutoff allows.
- Withdraw remains for draft/pending.
- Audit: `wfh_request_cancelled` / `wfh_request_withdrawn`; domain event `WfhRequestCancelled` on cancel.

---

## 7. Workflow automation (WP15)

Registered in `config/hrms_workflow_triggers.php` and wired in `AppServiceProvider` → `RunTriggeredWorkflows`:

| Event | Trigger key | Entity |
|---|---|---|
| `WfhRequestSubmitted` | `wfh.request_submitted` | `wfh_request` |
| `WfhRequestApproved` | `wfh.request_approved` | `wfh_request` |
| `WfhRequestRejected` | `wfh.request_rejected` | `wfh_request` |
| `WfhRequestCancelled` | `wfh.request_cancelled` | `wfh_request` |

Uses the **existing** Workflow Platform (no new engine). Notify-user actions support `wfh_request` via merged HRMS entities.

---

## 8. Transfer behavior (WP16)

| Change | Behavior |
|---|---|
| **Organization transfer** | `WfhPolicyService::handleEmployeeOrganizationTransfer` ends active assignments and cancels draft/pending/approved requests in the prior org (audit + tenant isolation). Hooked from `EmployeeService::updateEmployee` when `organization_id` changes. |
| **Branch change** | WFH assignments remain active; geofence re-resolves against the new branch. |

Note: composite FKs on related HRMS tables may block raw `employees.organization_id` updates until a full transfer pathway remaps dependent rows; the WFH cleanup handler is the supported invalidation API.

---

## 9. Multi-day requests (WP17)

- Forms accept `start_date` / `end_date` (single-day via `work_date` still supported).
- `work_date` is set to `start_date` for backward compatibility.
- Range validated (end ≥ start, max days, allowed weekdays, leave overlap, WFH conflicts, attendance locks per day).
- Approval/cancel apply to the whole request range.
- Resolution matches any date within `[start_date, end_date]`.

---

## 10. Attendance integration

`AttendanceVerificationService` consults `WfhPolicyService::resolveForDate` before geofence enforcement.

- WFH + `bypass_geofence` → office fence not required.
- Metadata: nested `wfh` + `wfh_exemption` (+ `geofence_skipped` in geofence mode).
- `record_gps_when_wfh` still requires coordinates.
- Biometric modes remain enforced.
- Versioning / verification audits unchanged.

Calendar: planned WFH days (no punch) render as `remote` / WFH; leave still outranks.

---

## 11. UX (WP18)

- ESS: today status, geofence exemption indicator, upcoming approved WFH, date-range request form, approval step history, cancel approved.
- HR request list/show/approval queue: date range labels, approval history with timestamps/comments, geofence exemption note.
- Calendar: WFH visibility without requiring a punch.

---

## 12. Optional location / map (WP19)

**Deferred — not required.** Geofence remains org/branch-managed. WFH bypasses fence when policy allows; optional GPS recording does not need a map picker. No change to geofence enforcement.

---

## 13. RBAC / navigation

Permissions: `wfh.view`, `wfh.manage`, `wfh.approve`.

Routes:

- Org settings: `organization.settings.wfh-policies.*`
- HR: `hrms.wfh.assignments.*`, `hrms.wfh.requests.*`
- ESS: `ess.wfh.index|store|destroy|cancel`

---

## 14. Key classes

| Class | Role |
|---|---|
| `WfhPolicyService` | Org policy, assignments, date resolution, transfer cleanup |
| `WfhRequestService` | Submit / approve / reject / cancel / withdraw (range + leave guards) |
| `WfhPolicy` | Authorization |
| `EmployeeService` | Invokes WFH transfer cleanup on org change |
| `AttendanceVerificationService` | Geofence/GPS/biometric + WFH exemption |
| `AttendanceCalendarService` | Leave > WFH visual precedence |

---

## 15. Tests (WP20)

Primary: `php artisan test tests/Feature/WfhPolicyTest.php --compact` — **17 passed**.

Covers: permanent/selected/daily, precedence, cancel, geofence, GPS, biometric, HTTP 403, tenant isolation, leave conflict, ESS cancel, multi-day, org-transfer cleanup, branch change.

Workflow:

- `HrmsWorkflowTriggerRegistrationTest` (WFH triggers in catalog + listener)
- `HrmsWorkflowRuntimeTest::test_wfh_workflows_notify_user_with_tenant_isolation`

### Serial regression results (2026-08-12)

| Batch | Result |
|---|---|
| WFH (`WfhPolicyTest`) | 17 passed |
| Attendance geo + `HrmsAttendanceTest` + geofence unit | 20 passed |
| `HrmsFoundationTest` + `HrmsEssTest` + `HrmsLeaveTest` | 36 passed |
| `DynamicRbacTest` + `RbacTest` + `OrganizationSwitchingTest` | 27 passed |
| `AttendanceCalendarTest` + workflow catalog registration | 13 passed |
| Workflow runtime (leave + WFH notify isolation) | 4 passed (earlier WP15 batch) |

**Totals (targeted):** **117+** assertions suites green across WFH / attendance / HRMS / leave / RBAC / tenant / workflow / calendar.

Logs: `storage/logs/wfh-wp13-wp17.log`, `wfh-wp15-workflow.log`, `wfh-wp20-*.log`.

**Application defects fixed in follow-ups:** leave/activation suppression, ESS cancel route, workflow registration, multi-day schema, transfer cleanup, calendar planned-WFH visibility.

---

## 16. Completion gate

- [x] Leave conflict handling
- [x] Approved WFH cancellation
- [x] Workflow automation
- [x] Employee transfer handling
- [x] Multi-day WFH
- [x] UX enhancements
- [x] Targeted regression green
- [x] Documentation updated

---

## 17. Still deferred

- Soft-fail / manager override for out-of-geofence punches (Phase 10.8)
- Map / location picker for WFH (WP19 evaluated — not required)
- Dedicated `attendance_records.is_wfh` column (metadata + resolver used)
- Full cross-org employee remapping of all HRMS child FKs (WFH cleanup handler ready; broader transfer pipeline separate)
