<?php

namespace App\Services;

use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LeadFollowUpService
{
    public function organizationTimezone(): string
    {
        return app(TenantContext::class)->get()?->timezone
            ?? config('app.timezone', 'UTC');
    }

    public function organizationNow(): Carbon
    {
        return now($this->organizationTimezone());
    }

    public function parseInput(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value, $this->organizationTimezone())
            ->seconds(0)
            ->utc();
    }

    public function minInputValue(): string
    {
        return $this->organizationNow()->addMinute()->format('Y-m-d\TH:i');
    }

    /**
     * @return array<string, mixed>
     */
    public function validationRules(): array
    {
        return [
            'next_follow_up_at' => ['nullable', 'date', new \App\Rules\FutureOrganizationDateTime],
            'next_follow_up_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function formatForInput(?Carbon $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value->copy()
            ->timezone($this->organizationTimezone())
            ->format('Y-m-d\TH:i');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function dueForAlertPayloads(int $limit = 10): Collection
    {
        return Lead::query()
            ->dueForFollowUpAlert()
            ->with(['assignee'])
            ->orderBy('next_follow_up_at')
            ->limit($limit)
            ->get()
            ->map(fn (Lead $lead) => $this->toAlertPayload($lead));
    }

    /**
     * @return array<string, mixed>
     */
    public function toAlertPayload(Lead $lead): array
    {
        $followUpAt = $lead->next_follow_up_at?->copy()->timezone($this->organizationTimezone());

        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'company' => $lead->company,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'status' => $lead->status_label,
            'priority' => $lead->priority_label,
            'assigned_to' => $lead->assignee?->name,
            'next_follow_up_at' => $followUpAt?->toIso8601String(),
            'next_follow_up_at_formatted' => $followUpAt?->format('M j, Y g:i A'),
            'next_follow_up_note' => $lead->next_follow_up_note,
            'url' => route('leads.show', $lead),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function normalizeValidatedFollowUp(array $validated): array
    {
        if (array_key_exists('next_follow_up_at', $validated)) {
            $validated['next_follow_up_at'] = $this->parseInput($validated['next_follow_up_at']);
        }

        if (($validated['next_follow_up_at'] ?? null) === null) {
            $validated['next_follow_up_note'] = null;
        }

        return $validated;
    }
}
