# Employee Profile APIs

**Status: No REST API endpoints exist for employee self-service profile.**

Profile view and update are **web ESS routes** only.

---

## API Availability

| Endpoint | Status |
|----------|--------|
| `GET /api/v1/ess/profile` | **Not implemented** |
| `PUT /api/v1/ess/profile` | **Not implemented** |
| `GET /api/v1/ess/profile/organization` | **Not implemented** |
| `GET /api/v1/ess/profile/manager` | **Not implemented** |

---

## Web ESS Implementation

**Controller:** `EssProfileController`  
**Prefix:** `/hrms/ess/profile`  
**Middleware:** `permission:ess.access`

| Method | Route | Action |
|--------|-------|--------|
| GET | `/hrms/ess/profile` | View own profile |
| PUT | `/hrms/ess/profile` | Update own profile |

### View Authorization

`EmployeePolicy::viewOwn` — requires `ess.access` + employee belongs to user.

### Loaded Relations (GET)

- `department`
- `designation`
- `reportingManager`
- `emergencyContacts`

---

## Editable Fields (PUT)

**FormRequest:** `UpdateEmployeeProfileRequest`  
**Authorization:** `EmployeePolicy::updateOwn`  
**Service:** `EmployeeService::updateOwnProfile()`

| Field | Rules |
|-------|-------|
| `phone` | nullable, string, max:50 |
| `mobile` | nullable, string, max:50 |
| `personal_email` | nullable, email, max:255 |
| `address_line_1` | nullable, string, max:255 |
| `address_line_2` | nullable, string, max:255 |
| `city` | nullable, string, max:100 |
| `state` | nullable, string, max:100 |
| `postal_code` | nullable, string, max:30 |
| `country` | nullable, string, max:100 |
| `emergency_contacts` | nullable, array |
| `emergency_contacts.*.name` | required_with:emergency_contacts, string, max:255 |
| `emergency_contacts.*.relationship` | nullable, string, max:100 |
| `emergency_contacts.*.phone` | required_with:emergency_contacts, string, max:50 |
| `emergency_contacts.*.email` | nullable, email, max:255 |
| `emergency_contacts.*.is_primary` | sometimes, boolean |

---

## Read-Only Fields (Not Editable via ESS)

Typically managed by HR admin (`HrmsEmployeeController`):

| Field | Source |
|-------|--------|
| `first_name`, `last_name` | HR admin |
| `employee_code` | HR admin |
| `email` (work) | HR admin |
| `department_id` | HR admin |
| `designation_id` | HR admin |
| `branch_id` | HR admin |
| `reporting_manager_id` | HR admin |
| `status` | HR admin |
| `employment_type` | HR admin |
| `date_of_joining` | HR admin |
| `salary` | HR admin / payroll |

---

## Partial Data via Lookups

`GET /api/v1/lookups/employees?id={own_employee_id}`

Requires `hrms.view` (not `ess.access`). Returns minimal metadata:

```json
{
  "data": [{
    "id": 10,
    "label": "John Doe",
    "subtitle": "EMP001 · Software Engineer",
    "badge": "Engineering",
    "metadata": {
      "employee_code": "EMP001",
      "email": "john@company.com",
      "mobile": "+1234567890",
      "department": "Engineering",
      "designation": "Software Engineer",
      "branch": "Head Office",
      "user_id": 5
    }
  }]
}
```

Insufficient for full profile screen (no address, emergency contacts, manager details).

---

## Organization Information

Displayed on web profile from `Employee` model relations:

| Data | Relation |
|------|----------|
| Department | `employee.department` |
| Designation | `employee.designation` |
| Branch | `employee.branch` |
| Manager | `employee.reportingManager` |
| Organization | `employee.organization` |

No API exposes this for ESS.

---

## Permissions

| Permission | Purpose |
|------------|---------|
| `ess.access` | ESS profile (web) |
| `hrms.view` | View employee records (admin) |
| `hrms.manage` | Edit employee records (admin) |

---

## Recommended Future API

```
GET  /api/v1/ess/profile
PUT  /api/v1/ess/profile
```

Response should include:

```json
{
  "data": {
    "id": 10,
    "employee_code": "EMP001",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@company.com",
    "phone": null,
    "mobile": "+1234567890",
    "personal_email": "john.personal@email.com",
    "address": {
      "line_1": "123 Main St",
      "city": "Mumbai",
      "state": "MH",
      "postal_code": "400001",
      "country": "India"
    },
    "department": { "id": 1, "name": "Engineering" },
    "designation": { "id": 2, "name": "Software Engineer" },
    "branch": { "id": 1, "name": "Head Office" },
    "manager": { "id": 5, "name": "Jane Manager" },
    "emergency_contacts": [
      {
        "name": "Emergency Contact",
        "relationship": "Spouse",
        "phone": "+1234567890",
        "is_primary": true
      }
    ],
    "editable_fields": ["phone", "mobile", "personal_email", "address_*", "emergency_contacts"]
  }
}
```
