<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\ProjectMention;
use App\Models\User;
use App\Services\TenantContext;

class MentionsWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'project_mentions';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.mentions.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $query = ProjectMention::query()
            ->where('mentioned_user_id', $user->id);

        $mentions = (clone $query)
            ->with([
                'project:id,name,slug,project_number',
                'task:id,title',
                'mentionedBy:id,name',
            ])
            ->latest()
            ->limit(5)
            ->get();

        return [
            'count' => (clone $query)->count(),
            'unread_count' => (clone $query)->whereNull('read_at')->count(),
            'mentions' => $mentions,
        ];
    }
}
