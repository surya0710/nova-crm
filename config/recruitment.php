<?php

use App\Services\Recruitment\Providers\CompanyCareersSiteProvider;
use App\Services\Recruitment\Providers\CustomMeetingUrlProvider;
use App\Services\Recruitment\Providers\GoogleCalendarProvider;
use App\Services\Recruitment\Providers\GoogleMeetProvider;
use App\Services\Recruitment\Providers\IndeedJobBoardProvider;
use App\Services\Recruitment\Providers\InternalResumeParsingProvider;
use App\Services\Recruitment\Providers\JitsiMeetProvider;
use App\Services\Recruitment\Providers\LinkedInJobsProvider;
use App\Services\Recruitment\Providers\MicrosoftTeamsMeetingProvider;
use App\Services\Recruitment\Providers\NaukriJobBoardProvider;
use App\Services\Recruitment\Providers\OutlookCalendarProvider;
use App\Services\Recruitment\Providers\PlaceholderBackgroundVerificationProvider;
use App\Services\Recruitment\Providers\ZoomMeetingProvider;

/**
 * Recruitment Integrations (Phase 11.6).
 *
 * Platform owns credentials, OAuth, tokens, health, and retry management patterns.
 * Recruitment owns domain integration orchestration. Adapters never write Eloquent.
 */
return [

    'providers' => [

        'statuses' => [
            'connected',
            'disconnected',
            'expired',
            'error',
        ],

        'categories' => [
            'calendar',
            'job_board',
            'resume_parsing',
            'background_verification',
            'meeting',
        ],

        /*
        | Catalog entries for UI discovery. Drivers are registered adapters.
        | Coming-soon providers appear in the catalog without a driver class.
        */
        'catalog' => [
            'google_calendar' => [
                'name' => 'Google Calendar',
                'category' => 'calendar',
                'capabilities' => ['oauth', 'calendar_events', 'meeting_links'],
            ],
            'outlook_calendar' => [
                'name' => 'Microsoft Outlook Calendar',
                'category' => 'calendar',
                'capabilities' => ['oauth', 'calendar_events', 'meeting_links'],
            ],
            'linkedin_jobs' => [
                'name' => 'LinkedIn Jobs',
                'category' => 'job_board',
                'capabilities' => ['job_publish', 'job_update', 'job_close', 'status_sync'],
            ],
            'indeed' => [
                'name' => 'Indeed',
                'category' => 'job_board',
                'capabilities' => ['job_publish', 'job_update', 'job_close', 'status_sync'],
            ],
            'naukri' => [
                'name' => 'Naukri',
                'category' => 'job_board',
                'capabilities' => ['job_publish', 'job_update', 'job_close', 'status_sync'],
            ],
            'company_careers' => [
                'name' => 'Company Careers Site',
                'category' => 'job_board',
                'capabilities' => ['job_publish', 'job_update', 'job_close', 'status_sync'],
            ],
            'internal_resume_parser' => [
                'name' => 'Internal Resume Parser',
                'category' => 'resume_parsing',
                'capabilities' => ['resume_parse'],
            ],
            'affinda' => [
                'name' => 'Affinda',
                'category' => 'resume_parsing',
                'capabilities' => ['resume_parse'],
                'coming_soon' => true,
            ],
            'rchilli' => [
                'name' => 'RChilli',
                'category' => 'resume_parsing',
                'capabilities' => ['resume_parse'],
                'coming_soon' => true,
            ],
            'sovren' => [
                'name' => 'Sovren',
                'category' => 'resume_parsing',
                'capabilities' => ['resume_parse'],
                'coming_soon' => true,
            ],
            'placeholder_bgv' => [
                'name' => 'Background Verification (Placeholder)',
                'category' => 'background_verification',
                'capabilities' => ['bgv_submit', 'bgv_status', 'bgv_documents'],
            ],
            'google_meet' => [
                'name' => 'Google Meet',
                'category' => 'meeting',
                'capabilities' => ['meeting_links'],
            ],
            'microsoft_teams' => [
                'name' => 'Microsoft Teams',
                'category' => 'meeting',
                'capabilities' => ['meeting_links'],
            ],
            'zoom' => [
                'name' => 'Zoom',
                'category' => 'meeting',
                'capabilities' => ['meeting_links'],
            ],
            'jitsi_meet' => [
                'name' => 'Jitsi Meet',
                'category' => 'meeting',
                'capabilities' => ['meeting_links'],
            ],
            'custom_meeting_url' => [
                'name' => 'Custom Meeting URL',
                'category' => 'meeting',
                'capabilities' => ['meeting_links'],
            ],
        ],

        'drivers' => [
            GoogleCalendarProvider::class,
            OutlookCalendarProvider::class,
            LinkedInJobsProvider::class,
            IndeedJobBoardProvider::class,
            NaukriJobBoardProvider::class,
            CompanyCareersSiteProvider::class,
            InternalResumeParsingProvider::class,
            PlaceholderBackgroundVerificationProvider::class,
            GoogleMeetProvider::class,
            MicrosoftTeamsMeetingProvider::class,
            ZoomMeetingProvider::class,
            JitsiMeetProvider::class,
            CustomMeetingUrlProvider::class,
        ],
    ],

    'communication' => [
        'channels' => ['email', 'sms', 'whatsapp'],
        'template_keys' => [
            'application_received',
            'interview_invitation',
            'interview_reminder',
            'interview_rescheduled',
            'offer_sent',
            'offer_accepted',
            'offer_rejected',
            'offer_expired',
        ],
        'variables' => [
            'candidate_name',
            'job_title',
            'company_name',
            'interview_date',
            'interviewer',
            'offer_salary',
            'joining_date',
        ],
        'statuses' => [
            'draft',
            'pending_approval',
            'active',
            'inactive',
        ],
    ],

    'background_verification' => [
        'statuses' => [
            'pending',
            'in_progress',
            'completed',
            'failed',
            'cancelled',
        ],
    ],

    'webhooks' => [
        'events' => [
            'application_submitted' => 'recruitment.application_submitted',
            'interview_scheduled' => 'recruitment.interview_scheduled',
            'interview_completed' => 'recruitment.interview_completed',
            'offer_sent' => 'recruitment.offer_sent',
            'offer_accepted' => 'recruitment.offer_accepted',
            'candidate_hired_recommendation' => 'recruitment.hiring_approved',
        ],
        'max_attempts' => (int) env('RECRUITMENT_WEBHOOK_MAX_ATTEMPTS', 5),
        'retry_backoff_seconds' => [60, 300, 900, 3600, 7200],
        'timeout_seconds' => (int) env('RECRUITMENT_WEBHOOK_TIMEOUT', 10),
    ],

    'job_board' => [
        'listing_statuses' => [
            'pending',
            'published',
            'updated',
            'closed',
            'failed',
        ],
        'max_publish_attempts' => (int) env('RECRUITMENT_JOB_BOARD_MAX_ATTEMPTS', 5),
    ],

    'calendar' => [
        'event_statuses' => [
            'pending',
            'synced',
            'updated',
            'cancelled',
            'failed',
        ],
    ],

    'resume_parsing' => [
        'request_statuses' => [
            'pending',
            'processing',
            'completed',
            'failed',
        ],
    ],
];
