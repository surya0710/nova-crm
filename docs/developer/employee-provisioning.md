# Employee Synchronization (Developer Guide)

## Problem

Employees could previously be created through divergent paths (HRMS create, user link, team membership) with incomplete records.

## Solution

`App\Services\Hrms\EmployeeProvisioningService` is the single provisioning entry point.

### Responsibilities

- User creation or reuse
- Employee creation (delegates persistence/profile sync to `EmployeeService`)
- Organization membership + role assignment
- Optional dashboard provisioning hook
- Welcome notification
- Audit event `employee_provisioned`

### Entry points

| Entry | Method |
|-------|--------|
| HRMS create with user | `EmployeeController@store` → `provision()` |
| Link / create user on employee | `EmployeeService::createAndLinkUser()` → `provisionUserForEmployee()` |
| Import | `provisionFromImport()` |
| API | `provisionFromApi()` |

### Invariants

Always aim for:

1. User (when requested)
2. Employee
3. Employee profile relations (via `EmployeeService::syncProfile`)
4. Organization membership
5. Role assignment

Do **not** duplicate onboarding logic in controllers.
