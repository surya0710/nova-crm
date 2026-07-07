<?php

use App\Services\OrganizationTerminology;

if (! function_exists('crm_term')) {
    function crm_term(string $key): string
    {
        return app(OrganizationTerminology::class)->get($key);
    }
}
