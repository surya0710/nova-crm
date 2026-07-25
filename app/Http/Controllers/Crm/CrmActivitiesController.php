<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Services\LeadFollowUpService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CrmActivitiesController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenant, LeadFollowUpService $followUps): View
    {
        abort_unless($request->user()->hasPermission('leads.view'), 403);

        $view = $request->query('view', 'list');
        if (! in_array($view, ['list', 'timeline', 'calendar'], true)) {
            $view = 'list';
        }

        $dueFollowUps = collect();
        $todaysFollowUps = collect();
        $recentNotes = collect();
        $weekStrip = collect();
        $timelineItems = collect();
        $calendarGroups = collect();

        if ($view === 'list') {
            $dueFollowUps = $followUps->dueForAlertPayloads(20);

            $start = $followUps->organizationNow()->copy()->startOfDay()->utc();
            $end = $followUps->organizationNow()->copy()->endOfDay()->utc();

            $todaysFollowUps = Lead::query()
                ->with('assignee')
                ->whereNotNull('next_follow_up_at')
                ->whereBetween('next_follow_up_at', [$start, $end])
                ->orderBy('next_follow_up_at')
                ->limit(25)
                ->get();

            $recentNotes = $this->recentLeadNotes();
            $weekStrip = $this->weekStrip($followUps);
        }

        if ($view === 'timeline') {
            $timelineItems = $this->timelineItems($followUps);
        }

        if ($view === 'calendar') {
            $calendarGroups = $this->calendarGroups($followUps);
        }

        return view('crm.activities', [
            'organization' => $tenant->get(),
            'view' => $view,
            'dueFollowUps' => $dueFollowUps,
            'todaysFollowUps' => $todaysFollowUps,
            'recentNotes' => $recentNotes,
            'weekStrip' => $weekStrip,
            'timelineItems' => $timelineItems,
            'calendarGroups' => $calendarGroups,
        ]);
    }

    /**
     * @return Collection<int, LeadNote>
     */
    protected function recentLeadNotes(): Collection
    {
        return LeadNote::query()
            ->with(['user', 'lead'])
            ->latest()
            ->limit(20)
            ->get();
    }

    /**
     * @return Collection<int, array{date: Carbon, label: string, count: int, is_today: bool}>
     */
    protected function weekStrip(LeadFollowUpService $followUps): Collection
    {
        $tz = $followUps->organizationTimezone();
        $weekStart = $followUps->organizationNow()->copy()->startOfWeek();
        $rangeStart = $weekStart->copy()->startOfDay()->utc();
        $rangeEnd = $weekStart->copy()->addDays(6)->endOfDay()->utc();

        $counts = Lead::query()
            ->whereNotNull('next_follow_up_at')
            ->whereBetween('next_follow_up_at', [$rangeStart, $rangeEnd])
            ->get(['next_follow_up_at'])
            ->groupBy(fn (Lead $lead) => $lead->next_follow_up_at->copy()->timezone($tz)->toDateString())
            ->map->count();

        $today = $followUps->organizationNow()->toDateString();

        return collect(range(0, 6))->map(function (int $offset) use ($weekStart, $counts, $today) {
            $day = $weekStart->copy()->addDays($offset);
            $key = $day->toDateString();

            return [
                'date' => $day,
                'label' => $day->format('D j'),
                'count' => (int) ($counts[$key] ?? 0),
                'is_today' => $key === $today,
            ];
        });
    }

    /**
     * @return Collection<int, array{type: string, actor: ?string, label: ?string, body: string, timestamp: ?Carbon, url: ?string}>
     */
    protected function timelineItems(LeadFollowUpService $followUps): Collection
    {
        $tz = $followUps->organizationTimezone();

        $followUpItems = Lead::query()
            ->with('assignee')
            ->whereNotNull('next_follow_up_at')
            ->orderByDesc('next_follow_up_at')
            ->limit(40)
            ->get()
            ->map(function (Lead $lead) use ($tz) {
                $at = $lead->next_follow_up_at?->copy()->timezone($tz);

                return [
                    'type' => 'follow_up',
                    'actor' => $lead->assignee?->name,
                    'label' => $lead->name,
                    'body' => $lead->next_follow_up_note
                        ?: __('Follow-up scheduled for :when', ['when' => $at?->format('M j, Y g:i A') ?? '—']),
                    'timestamp' => $lead->next_follow_up_at,
                    'url' => route('leads.show', $lead),
                ];
            });

        $noteItems = $this->recentLeadNotes()->map(fn (LeadNote $note) => [
            'type' => 'note',
            'actor' => $note->user?->name,
            'label' => $note->lead?->name,
            'body' => $note->body,
            'timestamp' => $note->created_at,
            'url' => $note->lead ? route('leads.show', $note->lead) : null,
        ]);

        return $followUpItems
            ->merge($noteItems)
            ->sortByDesc(fn (array $item) => $item['timestamp']?->timestamp ?? 0)
            ->values();
    }

    /**
     * @return Collection<string, array{date: Carbon, label: string, items: Collection<int, Lead>}>
     */
    protected function calendarGroups(LeadFollowUpService $followUps): Collection
    {
        $tz = $followUps->organizationTimezone();
        $startLocal = $followUps->organizationNow()->copy()->startOfDay();
        $endLocal = $followUps->organizationNow()->copy()->addDays(13)->endOfDay();

        $leads = Lead::query()
            ->with('assignee')
            ->whereNotNull('next_follow_up_at')
            ->whereBetween('next_follow_up_at', [$startLocal->copy()->utc(), $endLocal->copy()->utc()])
            ->orderBy('next_follow_up_at')
            ->get()
            ->groupBy(fn (Lead $lead) => $lead->next_follow_up_at->copy()->timezone($tz)->toDateString());

        return collect(range(0, 13))->mapWithKeys(function (int $offset) use ($startLocal, $leads, $tz) {
            $day = $startLocal->copy()->addDays($offset);
            $key = $day->toDateString();

            return [
                $key => [
                    'date' => $day,
                    'label' => $day->timezone($tz)->format('D, M j'),
                    'items' => $leads->get($key, collect()),
                ],
            ];
        });
    }
}
