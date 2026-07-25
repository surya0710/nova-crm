<?php

namespace App\Services\Recruitment;

use App\Models\Organization;
use App\Models\RecruitmentCommunicationTemplate;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecruitmentCommunicationService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @return list<string>
     */
    public function availableTemplateKeys(): array
    {
        return config('recruitment.communication.template_keys', []);
    }

    /**
     * @return list<string>
     */
    public function availableVariables(): array
    {
        return config('recruitment.communication.variables', []);
    }

    public function createTemplate(Organization $organization, array $data, User $actor): RecruitmentCommunicationTemplate
    {
        $this->assertValidKey($data['key'] ?? '');
        $this->assertValidChannel($data['channel'] ?? 'email');

        return DB::transaction(function () use ($organization, $data, $actor) {
            $template = RecruitmentCommunicationTemplate::query()->create([
                'organization_id' => $organization->id,
                'key' => $data['key'],
                'name' => $data['name'],
                'channel' => $data['channel'] ?? 'email',
                'subject' => $data['subject'] ?? null,
                'body' => $data['body'],
                'variables' => $this->extractVariables($data['body'].' '.($data['subject'] ?? '')),
                'status' => 'draft',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($template, 'recruitment_communication_template_created', [
                'key' => $template->key,
                'channel' => $template->channel,
            ], $actor);

            return $template;
        });
    }

    public function updateTemplate(RecruitmentCommunicationTemplate $template, array $data, User $actor): RecruitmentCommunicationTemplate
    {
        if ($template->status === 'active') {
            throw ValidationException::withMessages([
                'status' => 'Active templates must be deactivated before editing.',
            ]);
        }

        return DB::transaction(function () use ($template, $data, $actor) {
            if (isset($data['key'])) {
                $this->assertValidKey($data['key']);
            }
            if (isset($data['channel'])) {
                $this->assertValidChannel($data['channel']);
            }

            $body = $data['body'] ?? $template->body;
            $subject = $data['subject'] ?? $template->subject;

            $template->update([
                'key' => $data['key'] ?? $template->key,
                'name' => $data['name'] ?? $template->name,
                'channel' => $data['channel'] ?? $template->channel,
                'subject' => $subject,
                'body' => $body,
                'variables' => $this->extractVariables($body.' '.($subject ?? '')),
                'status' => $template->status === 'pending_approval' ? 'pending_approval' : 'draft',
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($template, 'recruitment_communication_template_updated', [
                'key' => $template->key,
            ], $actor);

            return $template->fresh();
        });
    }

    public function submitForApproval(RecruitmentCommunicationTemplate $template, User $actor): RecruitmentCommunicationTemplate
    {
        if (! in_array($template->status, ['draft', 'inactive'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only draft or inactive templates can be submitted for approval.',
            ]);
        }

        $template->update([
            'status' => 'pending_approval',
            'updated_by' => $actor->id,
        ]);

        $this->auditLogger->log($template, 'recruitment_communication_template_submitted', [
            'key' => $template->key,
        ], $actor);

        return $template->fresh();
    }

    public function approveTemplate(RecruitmentCommunicationTemplate $template, User $actor): RecruitmentCommunicationTemplate
    {
        if ($template->status !== 'pending_approval') {
            throw ValidationException::withMessages([
                'status' => 'Only pending templates can be approved.',
            ]);
        }

        return DB::transaction(function () use ($template, $actor) {
            // One active template per key+channel.
            RecruitmentCommunicationTemplate::query()
                ->where('organization_id', $template->organization_id)
                ->where('key', $template->key)
                ->where('channel', $template->channel)
                ->where('id', '!=', $template->id)
                ->where('status', 'active')
                ->update(['status' => 'inactive', 'updated_by' => $actor->id]);

            $template->update([
                'status' => 'active',
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($template, 'recruitment_communication_template_approved', [
                'key' => $template->key,
                'channel' => $template->channel,
            ], $actor);

            return $template->fresh();
        });
    }

    public function deactivateTemplate(RecruitmentCommunicationTemplate $template, User $actor): RecruitmentCommunicationTemplate
    {
        $template->update([
            'status' => 'inactive',
            'updated_by' => $actor->id,
        ]);

        $this->auditLogger->log($template, 'recruitment_communication_template_deactivated', [
            'key' => $template->key,
        ], $actor);

        return $template->fresh();
    }

    /**
     * Render a template with variables. Only active templates may be rendered for delivery.
     *
     * @param  array<string, string|null>  $variables
     */
    public function render(RecruitmentCommunicationTemplate $template, array $variables): array
    {
        if (! $template->isActive()) {
            throw ValidationException::withMessages([
                'status' => 'Communication templates require approval before activation.',
            ]);
        }

        $replacements = [];
        foreach ($this->availableVariables() as $key) {
            $replacements['{{'.$key.'}}'] = (string) ($variables[$key] ?? '');
        }

        return [
            'subject' => strtr((string) $template->subject, $replacements),
            'body' => strtr($template->body, $replacements),
            'channel' => $template->channel,
            'key' => $template->key,
        ];
    }

    public function findActive(Organization $organization, string $key, string $channel = 'email'): ?RecruitmentCommunicationTemplate
    {
        return RecruitmentCommunicationTemplate::query()
            ->where('organization_id', $organization->id)
            ->where('key', $key)
            ->where('channel', $channel)
            ->where('status', 'active')
            ->first();
    }

    protected function assertValidKey(string $key): void
    {
        if (! in_array($key, $this->availableTemplateKeys(), true)) {
            throw ValidationException::withMessages([
                'key' => 'Invalid communication template key.',
            ]);
        }
    }

    protected function assertValidChannel(string $channel): void
    {
        if (! in_array($channel, config('recruitment.communication.channels', []), true)) {
            throw ValidationException::withMessages([
                'channel' => 'Invalid communication channel.',
            ]);
        }
    }

    /**
     * @return list<string>
     */
    protected function extractVariables(string $content): array
    {
        preg_match_all('/\{\{(\w+)\}\}/', $content, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
