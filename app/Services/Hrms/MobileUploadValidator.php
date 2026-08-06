<?php

namespace App\Services\Hrms;

use App\Contracts\VirusScanHook;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Validates MIME/size and runs the virus-scan hook. Does not persist files.
 */
class MobileUploadValidator
{
    public function __construct(
        protected VirusScanHook $virusScan,
    ) {}

    public function validate(UploadedFile $file, string $purpose): void
    {
        $config = config('hrms.mobile.uploads.'.$purpose, config('hrms.mobile.uploads.default', []));
        $maxKb = (int) ($config['max_kb'] ?? 5120);
        $mimes = $config['mimes'] ?? ['jpeg', 'jpg', 'png', 'pdf'];

        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                'file' => __('The file may not be greater than :max kilobytes.', ['max' => $maxKb]),
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $mime = strtolower((string) $file->getMimeType());

        $allowed = array_map('strtolower', (array) $mimes);
        $ok = in_array($extension, $allowed, true)
            || collect($allowed)->contains(fn (string $m) => str_contains($mime, $m));

        if (! $ok) {
            throw ValidationException::withMessages([
                'file' => __('The file type is not allowed.'),
            ]);
        }

        $this->virusScan->scan($file, $purpose);
    }
}
