# Attendance Dashboard API

Base path: `/api/v1/attendance`

Authentication: Sanctum  
Organization: `X-Organization-Id` header

## Employee dashboard

```
GET /api/v1/attendance/dashboard
```

Permission: `ess.access`

Response:
```json
{
  "data": {
    "date": "2026-07-21",
    "state": "checked_in",
    "state_label": "Checked In",
    "working_hours": {
      "worked_minutes": 222,
      "worked_label": "3h 42m",
      "expected_minutes": 540,
      "remaining_minutes": 318,
      "is_live": true,
      "clock_in_at": "2026-07-21T09:04:00+00:00"
    },
    "shift_info": {
      "name": "General Shift",
      "start_time": "09:00",
      "end_time": "18:00"
    },
    "indicator": {
      "key": "on_time",
      "label": "On Time",
      "color": "green"
    },
    "actions": {
      "can_check_in": false,
      "can_check_out": true
    }
  }
}
```

## Check in

```
POST /api/v1/attendance/check-in
```

Optional body: `{ "clock_in_at": "2026-07-21T09:00:00" }`

## Check out

```
POST /api/v1/attendance/check-out
```

Optional body: `{ "clock_out_at": "2026-07-21T18:00:00" }`

## Manager team summary

```
GET /api/v1/attendance/team-summary
```

Permission: `manager.dashboard`

Response:
```json
{
  "data": {
    "team_count": 10,
    "present": 8,
    "absent": 1,
    "leave": 1,
    "late": 2,
    "working": 5,
    "checked_out": 3,
    "late_employees": []
  }
}
```
