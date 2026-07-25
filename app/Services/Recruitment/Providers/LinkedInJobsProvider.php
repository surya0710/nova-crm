<?php

namespace App\Services\Recruitment\Providers;

class LinkedInJobsProvider extends AbstractJobBoardProvider
{
    public function slug(): string
    {
        return 'linkedin_jobs';
    }

    public function displayName(): string
    {
        return 'LinkedIn Jobs';
    }
}
