# HRMS Troubleshooting Overview

## Problem
Unexpected issues in HRMS transactions or workflows.

## Symptoms
- Incorrect attendance/leave state
- Payroll run validation or publishing failure
- Missing review cycle actions
- Employee cannot log in / invitation expired
- Employee Workspace (ESS) missing after hire

## Possible Causes
- Policy misconfiguration
- Missing prerequisites or invalid inputs
- Permission restrictions
- Pending or expired invitation; locked/disabled account
- Portal access disabled on the user

## Resolution
1. Verify policy and record prerequisites.
2. Confirm role permissions and ownership.
3. Correct data and retry workflow action.
4. For login issues: check account status on the employee profile; Resend Invitation, Unlock, or Send Password Reset as appropriate. See [Employee Login](../user-guide/employee-login.md).

## Prevention
Use policy reviews, validation checks, and periodic access audits. Prefer invitation-based provisioning for all new tenant users.
