<?php

namespace App\Services\Security;

use App\Contracts\VirusScanHook;
use Illuminate\Http\UploadedFile;

class NoopVirusScanHook implements VirusScanHook
{
    public function scan(UploadedFile $file, string $purpose = 'generic'): void
    {
        // Intentionally empty — wire a real scanner via config('hrms.mobile.virus_scan_hook').
    }
}
