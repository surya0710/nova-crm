<?php

namespace App\Contracts;

use App\Models\RecruitmentProvider;

/**
 * Resume parsing capability. Current adapters expose framework only — no AI parsing.
 */
interface ResumeParsingProviderInterface
{
    /**
     * @param  array{file_path?: string|null, file_contents?: string|null, mime_type?: string|null, filename?: string|null}  $document
     * @return array{
     *     ok: bool,
     *     contact?: array{name?: string|null, email?: string|null, phone?: string|null},
     *     skills?: list<string>,
     *     experience?: list<array<string, mixed>>,
     *     education?: list<array<string, mixed>>,
     *     raw?: array<string, mixed>,
     *     message?: string|null
     * }
     */
    public function parseResume(RecruitmentProvider $provider, array $document): array;
}
