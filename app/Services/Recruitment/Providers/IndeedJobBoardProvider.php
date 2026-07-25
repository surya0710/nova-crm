<?php

namespace App\Services\Recruitment\Providers;

class IndeedJobBoardProvider extends AbstractJobBoardProvider
{
    public function slug(): string
    {
        return 'indeed';
    }

    public function displayName(): string
    {
        return 'Indeed';
    }
}
