<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class InterviewsTodayWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'interviews_today';
    }

    public function subscriptionModule(): ?string
    {
        return 'recruitment';
    }

    public function permissionSlug(): ?string
    {
        return 'recruitment.interview.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        if (! Schema::hasTable('interview_rounds')) {
            return ['interviews' => [], 'count' => 0];
        }

        $interviews = \App\Models\InterviewRound::query()
            ->with(['jobApplication.candidate:id,first_name,last_name'])
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get()
            ->map(fn ($round) => [
                'id' => $round->id,
                'scheduled_at' => $round->scheduled_at?->toIso8601String(),
                'candidate' => trim(($round->jobApplication?->candidate?->first_name ?? '').' '.($round->jobApplication?->candidate?->last_name ?? '')) ?: 'Unknown',
                'status' => $round->status,
            ]);

        return [
            'count' => $interviews->count(),
            'interviews' => $interviews,
        ];
    }
}
