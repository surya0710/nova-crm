# Recruitment Technical Architecture

## Purpose
Document the layered architecture for the recruitment foundation module.

## Layers
```
Controller → Form Request → Recruitment Service → Models
Controller → Form Request → Analytics Service → Aggregation Queries → Views
Controller → Form Request → Integration Service → Provider Adapter → External API
```

## Integration Layer
- `RecruitmentIntegrationService` — facade for providers, calendar, job boards, resume parsing, BGV, webhooks, API, diagnostics, and retries
- `RecruitmentProviderService` — connect/disconnect and catalog cards
- `RecruitmentProviderRegistry` — resolves slug → adapter class
- Provider adapters under `App\Services\Recruitment\Providers` call external APIs and never write Eloquent
- Platform owns credential/health/retry patterns; Recruitment owns domain orchestration and persistence

## Services
- `JobRequisitionService` — requisition CRUD and approval transitions
- `JobOpeningService` — opening creation from approved requisitions, publishing
- `CandidateService` — candidate uniqueness, document metadata storage
- `JobApplicationService` — application creation and stage updates
- `InterviewStageService` — pipeline stage configuration and progression rules
- `InterviewRoundService` — interview scheduling, completion, cancellation
- `InterviewParticipantService` — interviewer assignment
- `EvaluationTemplateService` — reusable scorecard templates
- `CandidateEvaluationService` — structured evaluation submission
- `OfferTemplateService` — reusable offer letter templates
- `OfferLetterService` — offer generation, approval, send, accept/reject lifecycle
- `OfferApprovalService` — configurable approval workflow
- `OfferNegotiationService` — negotiation history tracking
- `HiringDecisionService` — hiring recommendations and HR handoff
- `CareerSiteService` — public careers CMS and published opening queries
- `CandidateAccountService` — portal registration and authentication
- `CandidateProfileService` — self-service profile updates and snapshots
- `ResumeService` — multi-resume management with default enforcement
- `PublicApplicationService` — guest/account apply, draft, withdraw flows
- `SavedJobService` — bookmark published openings
- `JobAlertService` — job alert subscriptions
- `RecruitmentKpiService` — executive KPI and time metric calculations
- `RecruitmentDashboardService` — executive and hiring-manager dashboards
- `RecruitmentAnalyticsService` — funnel, source, recruiter, candidate, opening, department analytics
- `RecruitmentTrendService` — time-series hiring and volume trends
- `RecruitmentReportService` — report compilation and saved reports
- `RecruitmentExportService` — CSV/Excel export streaming
- `RecruitmentAnalyticsCache` — versioned cache invalidation for analytics
- `RecruitmentIntegrationService` — integration facade and diagnostics
- `RecruitmentCalendarService` — interview calendar create/update/cancel
- `RecruitmentJobBoardService` — external publish/update/close/sync
- `ResumeParsingService` — resume parse requests and apply-to-candidate
- `BackgroundVerificationService` — BGV submit/status after hiring approval
- `RecruitmentWebhookService` — outbound endpoints and signed deliveries
- `RecruitmentApiService` — REST API pagination helpers
- `RecruitmentCommunicationService` — communication templates

## Provider Adapters
- Calendar: `GoogleCalendarProvider`, `OutlookCalendarProvider`
- Job boards: `LinkedInJobsProvider`, `IndeedJobBoardProvider`, `NaukriJobBoardProvider`, `CompanyCareersSiteProvider`
- Resume: `InternalResumeParsingProvider`
- BGV: `PlaceholderBackgroundVerificationProvider`
- Meeting (catalog only / coming soon): Google Meet, Microsoft Teams, Zoom

## Candidate Portal Authentication
- Guard: `candidate`
- Provider: `candidate_accounts`
- Separate from CRM `web` and employee authentication

## Multi-Tenancy
All models use `BelongsToOrganization` with composite foreign keys where referencing HRMS entities. Analytics and integrations never cross tenant boundaries.

## Events (Workflow Integration)
- `recruitment.requisition_approved`
- `recruitment.opening_published`
- `recruitment.candidate_created`
- `recruitment.application_submitted`
- `recruitment.interview_scheduled`
- `recruitment.interview_cancelled`
- `recruitment.interview_completed`
- `recruitment.evaluation_submitted`
- `recruitment.candidate_recommended`
- `recruitment.offer_generated`
- `recruitment.offer_approved`
- `recruitment.offer_sent`
- `recruitment.offer_accepted`
- `recruitment.offer_rejected`
- `recruitment.offer_expired`
- `recruitment.hiring_approved`
- `recruitment.candidate_registered`
- `recruitment.candidate_logged_in`
- `recruitment.resume_uploaded`
- `recruitment.job_applied`
- `recruitment.application_withdrawn`
- `recruitment.candidate_profile_updated`

`DispatchRecruitmentOutboundIntegrations` listens for application, interview, offer, and hiring events to fire webhooks and calendar sync safely.

## Data Model
```
JobRequisition → JobOpening → JobApplication → Candidate
Candidate → CandidateAccount / CandidateResume
CandidateAccount → CandidateSavedJob / CandidateJobAlert
Organization → CareerSiteSetting / CandidatePortalSetting
JobApplication → InterviewRound → InterviewParticipant
InterviewRound → CandidateEvaluation → EvaluationResponse
InterviewRound → RecruitmentCalendarEvent → RecruitmentProvider
EvaluationTemplate → EvaluationSection → EvaluationQuestion
OfferTemplate → OfferLetter → OfferApproval / OfferNegotiation
JobApplication → HiringDecision → RecruitmentBackgroundVerification
JobOpening → RecruitmentJobBoardListing → RecruitmentProvider
Organization → RecruitmentWebhookEndpoint → RecruitmentWebhookDelivery
Organization → RecruitmentSavedReport (user-owned report configurations)
Candidate → CandidateDocument (metadata + stored files)
```

## Public Routes
- `{organization-slug}/careers` — careers home
- `{organization-slug}/careers/jobs/{opening}` — job detail and apply
- `{organization-slug}/careers/dashboard` — candidate dashboard (authenticated)

## Out of Scope (Later)
AI hiring predictions, scheduled email reports, BI tool integrations, full Meet/Teams/Zoom meeting lifecycle APIs, historical calendar import, digital signatures, LinkedIn/Google candidate login, and employee onboarding record creation in HRMS.

## Related Documentation
See [integrations](../integrations.md), [calendar](../calendar.md), [job-boards](../job-boards.md), [webhooks](../webhooks.md), and [apis](../apis.md).
