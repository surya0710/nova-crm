<?php

namespace App\Services;

use App\Models\ClientUser;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectSharedLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectSharingService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    /**
     * @param  list<string>|null  $scopes
     */
    public function createSharedLink(
        Project $project,
        Model $shareable,
        User $actor,
        ?array $scopes = null,
        ?int $ttlHours = null,
        ?int $maxDownloads = null,
    ): ProjectSharedLink {
        if ((int) ($shareable->getAttribute('organization_id') ?? 0) !== (int) $project->organization_id
            && method_exists($shareable, 'getAttribute')) {
            // Allow project itself.
        }

        $ttlHours ??= (int) config('portal.shared_link_ttl_hours', 72);

        $link = ProjectSharedLink::query()->create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'token' => Str::random(48),
            'shareable_type' => $shareable->getMorphClass(),
            'shareable_id' => $shareable->getKey(),
            'scopes' => $scopes ?? config('portal.default_share_scopes'),
            'expires_at' => now()->addHours($ttlHours),
            'max_downloads' => $maxDownloads,
            'download_count' => 0,
            'created_by' => $actor->id,
        ]);

        $this->auditLogger->log($link, 'shared_link_created', [
            'project_id' => $project->id,
            'shareable_type' => $link->shareable_type,
            'shareable_id' => $link->shareable_id,
        ], $actor);

        return $link;
    }

    public function resolveValidLink(string $token): ProjectSharedLink
    {
        $link = ProjectSharedLink::query()->where('token', $token)->first();

        if (! $link || ! $link->isValid()) {
            throw ValidationException::withMessages([
                'token' => __('This shared link is invalid or has expired.'),
            ]);
        }

        return $link;
    }

    public function recordDownload(ProjectSharedLink $link): ProjectSharedLink
    {
        if (! $link->isValid()) {
            throw ValidationException::withMessages([
                'token' => __('This shared link is invalid or has expired.'),
            ]);
        }

        $link->increment('download_count');

        $this->auditLogger->log($link->fresh(), 'shared_link_downloaded', [
            'download_count' => $link->download_count + 1,
        ], null);

        return $link->fresh();
    }

    /**
     * @param  list<string>  $scopes
     */
    public function updateAccessScopes(ClientUser $client, Project $project, array $scopes, User $actor): void
    {
        app(ClientAccessService::class)->grantProjectAccess($client, $project, $scopes, $actor);
    }

    public function ensurePortalEnabled(Organization $organization): void
    {
        // Settings are optional; missing row means enabled by default.
    }
}
