<?php

namespace App\Services\Recruitment\Providers;

use App\Contracts\ResumeParsingProviderInterface;
use App\Models\RecruitmentProvider;

/**
 * Internal placeholder parser — exposes the framework without AI parsing.
 */
class InternalResumeParsingProvider extends AbstractRecruitmentProvider implements ResumeParsingProviderInterface
{
    public function slug(): string
    {
        return 'internal_resume_parser';
    }

    public function displayName(): string
    {
        return 'Internal Resume Parser';
    }

    public function category(): string
    {
        return 'resume_parsing';
    }

    public function capabilities(): array
    {
        return ['resume_parse'];
    }

    public function authorize(RecruitmentProvider $provider, array $context = []): array
    {
        return [
            'credentials' => [
                'access_token' => 'internal-parser',
                'token_type' => 'internal',
            ],
            'status' => RecruitmentProvider::STATUS_CONNECTED,
        ];
    }

    public function parseResume(RecruitmentProvider $provider, array $document): array
    {
        $filename = (string) ($document['filename'] ?? 'resume.pdf');

        return [
            'ok' => true,
            'contact' => [
                'name' => null,
                'email' => null,
                'phone' => null,
            ],
            'skills' => [],
            'experience' => [],
            'education' => [],
            'raw' => [
                'filename' => $filename,
                'mime_type' => $document['mime_type'] ?? null,
                'parser' => $this->slug(),
                'note' => 'Placeholder parse — AI providers will replace this later.',
            ],
            'message' => 'Resume accepted for placeholder parsing. No AI extraction performed.',
        ];
    }
}
