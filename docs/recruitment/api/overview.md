# Recruitment API Reference

## Purpose
Document web routes and service entry points for the recruitment foundation.

## Web Routes (authenticated)
| Route name | Method | Permission |
|------------|--------|------------|
| `hrms.recruitment.dashboard` | GET | `recruitment.view` |
| `hrms.recruitment.executive` | GET | `recruitment.analytics.view` |
| `hrms.recruitment.analytics` | GET | `recruitment.analytics.view` |
| `hrms.recruitment.reports.index` | GET | `recruitment.reports.view` |
| `hrms.recruitment.saved-reports.*` | REST | `recruitment.reports.view` / manage |
| `hrms.recruitment.saved-reports.share` | POST | `recruitment.reports.manage` |
| `hrms.recruitment.exports.index` | GET | `recruitment.reports.export` |
| `hrms.recruitment.exports.download` | POST | `recruitment.reports.export` |
| `hrms.recruitment.requisitions.*` | REST | `recruitment.view` / create/edit/delete |
| `hrms.recruitment.requisitions.submit` | POST | `recruitment.edit` |
| `hrms.recruitment.requisitions.approve` | POST | `recruitment.manage` |
| `hrms.recruitment.openings.publish` | POST | `recruitment.manage` |
| `hrms.recruitment.applications.stage` | POST | `recruitment.edit` |
| `hrms.recruitment.interview-stages.*` | REST | `recruitment.interview.view` / create/edit/delete |
| `hrms.recruitment.evaluation-templates.*` | REST | `recruitment.interview.view` / create/delete |
| `hrms.recruitment.interview-rounds.*` | REST | `recruitment.interview.view` / create/delete |
| `hrms.recruitment.interview-rounds.complete` | POST | `recruitment.interview.edit` |
| `hrms.recruitment.interview-rounds.cancel` | POST | `recruitment.interview.edit` |
| `hrms.recruitment.interview-rounds.evaluate` | GET | `recruitment.evaluate` |
| `hrms.recruitment.evaluations.*` | index/show/store | `recruitment.interview.view` / `recruitment.evaluate` |
| `hrms.recruitment.offer-templates.*` | REST | `recruitment.offer.view` / create/edit/delete |
| `hrms.recruitment.offers.*` | REST + actions | `recruitment.offer.view` / create/edit/delete |
| `hrms.recruitment.offers.submit` | POST | `recruitment.offer.edit` |
| `hrms.recruitment.offers.send` | POST | `recruitment.offer.edit` |
| `hrms.recruitment.offers.accept` | POST | `recruitment.offer.edit` |
| `hrms.recruitment.offer-approvals.*` | index/show + actions | `recruitment.offer.view` / `recruitment.offer.approve` |
| `hrms.recruitment.negotiations.*` | index/show/store | `recruitment.offer.view` / `recruitment.offer.edit` |
| `hrms.recruitment.hiring-decisions.*` | index/show/store | `recruitment.offer.view` / `recruitment.offer.create` |
| `hrms.recruitment.integrations.index` | GET | `recruitment.integration.view` |
| `hrms.recruitment.integrations.connect` | POST | `recruitment.integration.manage` |
| `hrms.recruitment.integrations.calendar` | GET/POST sync | `recruitment.integration.view` / manage |
| `hrms.recruitment.integrations.job-boards` | GET + publish/sync/close | `recruitment.integration.view` / manage |
| `hrms.recruitment.integrations.webhooks` | GET/POST + retry | `recruitment.webhook.view` |
| `hrms.recruitment.integrations.api-access` | GET | `recruitment.api.manage` |

## REST API v1
Base path `/api/v1/recruitment` (Sanctum + `X-Organization-Id` + `api.access` + recruitment permissions):

| Method | Path | Permission |
|--------|------|------------|
| GET | `/api/v1/recruitment/jobs` | `recruitment.view` |
| GET | `/api/v1/recruitment/jobs/{job}` | `recruitment.view` |
| GET | `/api/v1/recruitment/applications` | `recruitment.view` |
| GET | `/api/v1/recruitment/applications/{application}` | `recruitment.view` |
| GET | `/api/v1/recruitment/candidates` | `recruitment.view` |
| GET | `/api/v1/recruitment/candidates/{candidate}` | `recruitment.view` |
| GET | `/api/v1/recruitment/offers` | `recruitment.offer.view` |
| GET | `/api/v1/recruitment/offers/{offer}` | `recruitment.offer.view` |
| GET | `/api/v1/recruitment/reports` | `recruitment.reports.view` |
| GET | `/api/v1/recruitment/reports/{report}` | `recruitment.reports.view` |

Full consumer documentation: [apis](../apis.md). Webhooks: [webhooks](../webhooks.md).

## Service Methods
- `JobRequisitionService::createRequisition`, `submitForApproval`, `approveRequisition`
- `JobOpeningService::createOpeningFromRequisition`, `publishOpening`
- `CandidateService::createCandidate`, `uploadDocument`
- `JobApplicationService::createApplication`, `updateApplication`
- `InterviewStageService::ensureDefaultStages`, `createStage`, `updateStage`, `deleteStage`
- `InterviewRoundService::createRound`, `scheduleRound`, `completeRound`, `cancelRound`
- `InterviewParticipantService::assignParticipant`, `syncParticipants`
- `EvaluationTemplateService::createTemplate`, `updateTemplate`, `deleteTemplate`
- `CandidateEvaluationService::submitEvaluation`, `updateEvaluation`
- `OfferTemplateService::createTemplate`, `updateTemplate`, `deleteTemplate`
- `OfferLetterService::generateOffer`, `submitForApproval`, `sendOffer`, `acceptOffer`, `rejectOffer`
- `OfferApprovalService::approve`, `reject`, `returnForRevision`
- `OfferNegotiationService::recordNegotiation`, `updateNegotiation`
- `HiringDecisionService::recordDecision`, `updateDecision`
- `RecruitmentKpiService::executiveKpis`, `timeMetrics`, `averageTimeToHire`, `averageTimeToFill`
- `RecruitmentDashboardService::executiveDashboard`, `hiringManagerMetrics`
- `RecruitmentAnalyticsService::funnel`, `sourceEffectiveness`, `recruiterPerformance`, `candidateAnalytics`, `jobOpeningAnalytics`, `departmentAnalytics`
- `RecruitmentTrendService::trends`
- `RecruitmentReportService::compile`, `saveReport`, `shareReport`, `deleteReport`
- `RecruitmentExportService::export`
- `RecruitmentIntegrationService::diagnostics`, `connectProvider`, `processRetries`
- `RecruitmentCalendarService::syncInterviewEvent`, `cancelInterviewEvent`
- `RecruitmentJobBoardService::publishOpening`, `closeOpening`, `syncStatus`, `processRetries`
- `RecruitmentWebhookService::createEndpoint`, `dispatchEvent`, `deliver`, `processRetries`
- `RecruitmentApiService::paginateJobs`, `paginateApplications`, `paginateCandidates`, `paginateOffers`, `paginateReports`

## Validation
Form requests under `App\Http\Requests\Recruitment` enforce tenant-scoped existence rules and business preconditions (e.g. approved requisition for openings, published opening for external job boards). Analytics filter requests validate period/custom ranges and report/export types.

## Related Documentation
See [apis](../apis.md), [integrations](../integrations.md), and [webhooks](../webhooks.md).
