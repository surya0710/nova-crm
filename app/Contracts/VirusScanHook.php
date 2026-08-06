<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

/**
 * Future-ready virus scan hook for mobile/API uploads.
 * Default implementation is a no-op.
 */
interface VirusScanHook
{
    /**
     * @throws \Illuminate\Validation\ValidationException when the file is rejected
     */
    public function scan(UploadedFile $file, string $purpose = 'generic'): void;
}
