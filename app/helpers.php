<?php

use App\Services\OrganizationTerminology;

if (! function_exists('crm_term')) {
    function crm_term(string $key): string
    {
        return app(OrganizationTerminology::class)->get($key);
    }
}

if (! function_exists('trans_string')) {
    /**
     * Translate a key and always return a string.
     *
     * On case-insensitive filesystems, __("Attendance") can resolve to
     * lang/en/attendance.php and return an array. Fall back to the key.
     */
    function trans_string(?string $key, array $replace = [], ?string $locale = null): string
    {
        if ($key === null || $key === '') {
            return '';
        }

        $translated = __($key, $replace, $locale);

        return is_string($translated) ? $translated : $key;
    }
}
