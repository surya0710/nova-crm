<?php

namespace App\Services\Platform;

use App\Models\IndustryTemplate;
use App\Models\IndustryTemplateVersion;
use App\Models\PlatformUser;
use Illuminate\Support\Facades\DB;

class IndustryTemplatePublishService
{
    public function __construct(
        protected IndustryTemplatePayloadValidator $payloadValidator,
        protected PlatformAuditService $audit,
    ) {}

    public function publish(IndustryTemplate $template, PlatformUser $actor, ?string $changelog = null): IndustryTemplateVersion
    {
        return DB::transaction(function () use ($template, $actor, $changelog) {
            $payload = $this->payloadValidator->validate($template->draft_payload ?? []);
            $hash = $this->payloadValidator->hash($payload);
            $nextVersion = ((int) $template->versions()->max('version')) + 1;

            if ($template->currentVersion) {
                $template->currentVersion->update(['status' => 'superseded']);
            }

            $version = $template->versions()->create([
                'version' => $nextVersion,
                'schema_version' => (int) $payload['schema_version'],
                'payload' => $payload,
                'payload_hash' => $hash,
                'changelog' => $changelog,
                'status' => 'published',
                'published_by_platform_user_id' => $actor->id,
                'published_at' => now(),
            ]);

            $template->update([
                'status' => 'published',
                'current_version_id' => $version->id,
                'published_by_platform_user_id' => $actor->id,
                'published_at' => now(),
                'updated_by_platform_user_id' => $actor->id,
            ]);

            $this->audit->log('industry_template.published', $actor, null, [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'version_id' => $version->id,
                'version' => $version->version,
                'payload_hash' => $version->payload_hash,
            ], IndustryTemplate::class.':'.$template->id);

            return $version;
        });
    }
}
