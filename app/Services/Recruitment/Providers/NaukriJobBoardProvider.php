<?php

namespace App\Services\Recruitment\Providers;

class NaukriJobBoardProvider extends AbstractJobBoardProvider
{
    public function slug(): string
    {
        return 'naukri';
    }

    public function displayName(): string
    {
        return 'Naukri';
    }
}
