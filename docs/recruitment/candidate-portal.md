# Candidate Portal

## Purpose
The Candidate Portal provides separate authentication and self-service tools for job applicants. Candidate accounts are independent from employee users and CRM login.

## Features
- Candidate registration, login, and password reset
- Profile management with education, experience, skills, and preferences
- Multiple resume uploads with a single default resume
- Job applications with draft, submit, withdraw, and resume update flows
- Application status timeline without recruiter notes
- Saved jobs and job alert subscriptions
- Offer visibility for sent offers

## Architecture
Controller → Form Request → Service → Model

Services:
- `CandidateAccountService`
- `CandidateProfileService`
- `ResumeService`
- `PublicApplicationService`
- `SavedJobService`
- `JobAlertService`

## Authentication
- Guard: `candidate`
- Model: `candidate_accounts`
- Scoped by `organization_id` and email uniqueness per tenant

## Related Documentation
See [careers-site](careers-site.md) and [public-applications](public-applications.md).
