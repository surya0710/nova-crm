<?php

namespace App\Services;

use App\Events\TaskRecurrenceCreated;
use App\Events\TaskRecurrenceDeleted;
use App\Events\TaskRecurrenceUpdated;
use App\Models\Task;
use App\Models\TaskRecurrence;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TaskRecurrenceService
{
    /** @var list<string> */
    public const TYPES = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'];

    /** @var list<string> */
    public const END_TYPES = ['never', 'date', 'occurrences'];

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Task $task, array $data, User $actor): TaskRecurrence
    {
        if ($task->isArchived()) {
            throw ValidationException::withMessages([
                'task' => __('Archived tasks are read-only.'),
            ]);
        }

        return DB::transaction(function () use ($task, $data, $actor) {
            $payload = $this->normalizePayload($data);
            $from = $this->anchorFromTask($task);

            $recurrence = TaskRecurrence::query()->create([
                'organization_id' => $task->organization_id,
                'task_id' => $task->id,
                ...$payload,
                'generated_count' => 0,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'next_run_at' => $this->calculateNextRunAtFromValues($payload, $from),
            ]);

            $runtime = app(WorkflowRuntimeContext::class);
            event(TaskRecurrenceCreated::forModel(
                $recurrence,
                ['actor_id' => $actor->id, 'task_id' => $task->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $recurrence->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TaskRecurrence $recurrence, array $data, User $actor): TaskRecurrence
    {
        return DB::transaction(function () use ($recurrence, $data, $actor) {
            $payload = $this->normalizePayload(array_merge($recurrence->only([
                'recurrence_type',
                'interval',
                'days_of_week',
                'end_type',
                'end_date',
                'occurrences',
                'skip_holidays',
                'copy_attachments',
                'settings',
            ]), $data), partial: true);

            if (array_key_exists('is_active', $data)) {
                $payload['is_active'] = (bool) $data['is_active'];
            }

            $merged = array_merge($recurrence->attributesToArray(), $payload);
            $from = $recurrence->next_run_at
                ? Carbon::parse($recurrence->next_run_at)
                : ($recurrence->task ? $this->anchorFromTask($recurrence->task) : now());

            $payload['next_run_at'] = $this->calculateNextRunAtFromValues($merged, $from, recalculateFromNow: true);

            $recurrence->update($payload);
            $recurrence = $recurrence->fresh();

            $runtime = app(WorkflowRuntimeContext::class);
            event(TaskRecurrenceUpdated::forModel(
                $recurrence,
                ['actor_id' => $actor->id, 'changes' => array_keys($payload)],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $recurrence;
        });
    }

    public function delete(TaskRecurrence $recurrence, User $actor): void
    {
        $runtime = app(WorkflowRuntimeContext::class);
        event(TaskRecurrenceDeleted::forModel(
            $recurrence,
            ['actor_id' => $actor->id, 'task_id' => $recurrence->task_id],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $recurrence->delete();
    }

    public function calculateNextRunAt(TaskRecurrence $recurrence, ?CarbonInterface $from = null): ?Carbon
    {
        return $this->calculateNextRunAtFromValues(
            $recurrence->attributesToArray(),
            $from ?? ($recurrence->next_run_at ? Carbon::parse($recurrence->next_run_at) : now()),
        );
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function calculateNextRunAtFromValues(
        array $values,
        CarbonInterface $from,
        bool $recalculateFromNow = false,
    ): ?Carbon {
        $type = (string) ($values['recurrence_type'] ?? 'daily');
        $interval = max(1, (int) ($values['interval'] ?? 1));
        $endType = (string) ($values['end_type'] ?? 'never');
        $cursor = Carbon::parse($from)->startOfMinute();

        if ($recalculateFromNow && $cursor->lt(now())) {
            $cursor = now()->startOfMinute();
        }

        $candidate = $this->advance($cursor, $type, $interval, $values['days_of_week'] ?? null, $values['settings'] ?? null);

        if (! empty($values['skip_holidays']) && ! empty($values['organization_id'])) {
            $candidate = $this->skipHolidays((int) $values['organization_id'], $candidate, $type, $interval, $values);
        }

        if ($endType === 'date' && ! empty($values['end_date'])) {
            $endDate = Carbon::parse($values['end_date'])->endOfDay();
            if ($candidate->gt($endDate)) {
                return null;
            }
        }

        if ($endType === 'occurrences') {
            $limit = (int) ($values['occurrences'] ?? 0);
            $generated = (int) ($values['generated_count'] ?? 0);
            if ($limit > 0 && $generated >= $limit) {
                return null;
            }
        }

        return $candidate;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizePayload(array $data, bool $partial = false): array
    {
        $payload = [];

        if (! $partial || array_key_exists('recurrence_type', $data)) {
            $type = (string) ($data['recurrence_type'] ?? 'daily');
            if (! in_array($type, self::TYPES, true)) {
                throw ValidationException::withMessages([
                    'recurrence_type' => __('Invalid recurrence type.'),
                ]);
            }
            $payload['recurrence_type'] = $type;
        }

        if (! $partial || array_key_exists('interval', $data)) {
            $payload['interval'] = max(1, (int) ($data['interval'] ?? 1));
        }

        if (! $partial || array_key_exists('days_of_week', $data)) {
            $days = $data['days_of_week'] ?? null;
            if (is_string($days)) {
                $decoded = json_decode($days, true);
                $days = is_array($decoded) ? $decoded : null;
            }
            $payload['days_of_week'] = is_array($days) ? array_values($days) : null;
        }

        if (! $partial || array_key_exists('end_type', $data)) {
            $endType = (string) ($data['end_type'] ?? 'never');
            if (! in_array($endType, self::END_TYPES, true)) {
                throw ValidationException::withMessages([
                    'end_type' => __('Invalid end type.'),
                ]);
            }
            $payload['end_type'] = $endType;
        }

        if (! $partial || array_key_exists('end_date', $data)) {
            $payload['end_date'] = $data['end_date'] ?? null;
        }

        if (! $partial || array_key_exists('occurrences', $data)) {
            $payload['occurrences'] = isset($data['occurrences']) ? (int) $data['occurrences'] : null;
        }

        if (! $partial || array_key_exists('skip_holidays', $data)) {
            $payload['skip_holidays'] = (bool) ($data['skip_holidays'] ?? false);
        }

        if (! $partial || array_key_exists('copy_attachments', $data)) {
            $payload['copy_attachments'] = (bool) ($data['copy_attachments'] ?? false);
        }

        if (! $partial || array_key_exists('settings', $data)) {
            $payload['settings'] = $data['settings'] ?? null;
        }

        $endType = $payload['end_type'] ?? ($data['end_type'] ?? 'never');

        if ($endType === 'date' && empty($payload['end_date'] ?? $data['end_date'] ?? null)) {
            throw ValidationException::withMessages([
                'end_date' => __('An end date is required when end type is date.'),
            ]);
        }

        if ($endType === 'occurrences' && (int) ($payload['occurrences'] ?? $data['occurrences'] ?? 0) < 1) {
            throw ValidationException::withMessages([
                'occurrences' => __('Occurrences must be at least 1.'),
            ]);
        }

        return $payload;
    }

    protected function anchorFromTask(Task $task): Carbon
    {
        if ($task->due_at) {
            return Carbon::parse($task->due_at);
        }

        if ($task->due_date) {
            return Carbon::parse($task->due_date)->setTime(9, 0);
        }

        return now()->addDay()->setTime(9, 0);
    }

    /**
     * @param  list<int|string>|null  $daysOfWeek
     * @param  array<string, mixed>|null  $settings
     */
    protected function advance(
        CarbonInterface $from,
        string $type,
        int $interval,
        ?array $daysOfWeek,
        ?array $settings,
    ): Carbon {
        $cursor = Carbon::parse($from);

        return match ($type) {
            'daily' => $cursor->copy()->addDays($interval),
            'weekly' => $this->nextWeekly($cursor, $interval, $daysOfWeek),
            'monthly' => $cursor->copy()->addMonthsNoOverflow($interval),
            'quarterly' => $cursor->copy()->addMonthsNoOverflow(3 * $interval),
            'yearly' => $cursor->copy()->addYearsNoOverflow($interval),
            'custom' => $cursor->copy()->addDays(max(1, (int) ($settings['interval_days'] ?? $interval))),
            default => $cursor->copy()->addDays($interval),
        };
    }

    /**
     * @param  list<int|string>|null  $daysOfWeek
     */
    protected function nextWeekly(CarbonInterface $from, int $interval, ?array $daysOfWeek): Carbon
    {
        $cursor = Carbon::parse($from);
        $days = $this->normalizeDaysOfWeek($daysOfWeek);

        if ($days === []) {
            return $cursor->copy()->addWeeks($interval);
        }

        $probe = $cursor->copy()->addDay()->startOfDay()->setTime($cursor->hour, $cursor->minute, $cursor->second);
        $weeksAdvanced = 0;

        for ($i = 0; $i < 14 * max(1, $interval); $i++) {
            $isoDay = (int) $probe->dayOfWeekIso; // 1=Mon … 7=Sun
            if (in_array($isoDay, $days, true)) {
                if ($weeksAdvanced >= $interval - 1 || $interval === 1) {
                    return $probe;
                }
            }

            $probe->addDay();
            if ($probe->dayOfWeekIso === 1) {
                $weeksAdvanced++;
            }
        }

        return $cursor->copy()->addWeeks($interval);
    }

    /**
     * @param  list<int|string>|null  $daysOfWeek
     * @return list<int>
     */
    protected function normalizeDaysOfWeek(?array $daysOfWeek): array
    {
        if (! is_array($daysOfWeek) || $daysOfWeek === []) {
            return [];
        }

        $map = [
            'mon' => 1, 'monday' => 1,
            'tue' => 2, 'tuesday' => 2,
            'wed' => 3, 'wednesday' => 3,
            'thu' => 4, 'thursday' => 4,
            'fri' => 5, 'friday' => 5,
            'sat' => 6, 'saturday' => 6,
            'sun' => 7, 'sunday' => 7,
        ];

        $normalized = [];

        foreach ($daysOfWeek as $day) {
            if (is_numeric($day)) {
                $value = (int) $day;
                if ($value === 0) {
                    $value = 7;
                }
                if ($value >= 1 && $value <= 7) {
                    $normalized[] = $value;
                }

                continue;
            }

            $key = Str::lower((string) $day);
            if (isset($map[$key])) {
                $normalized[] = $map[$key];
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function skipHolidays(
        int $organizationId,
        Carbon $candidate,
        string $type,
        int $interval,
        array $values,
    ): Carbon {
        if (! class_exists(\App\Models\Holiday::class)) {
            return $candidate;
        }

        $guard = 0;
        while ($this->isHoliday($organizationId, $candidate) && $guard < 30) {
            $candidate = $this->advance(
                $candidate,
                $type === 'weekly' ? 'daily' : $type,
                $type === 'weekly' ? 1 : $interval,
                $values['days_of_week'] ?? null,
                $values['settings'] ?? null,
            );
            $guard++;
        }

        return $candidate;
    }

    protected function isHoliday(int $organizationId, CarbonInterface $date): bool
    {
        /** @var class-string<\App\Models\Holiday> $holidayClass */
        $holidayClass = \App\Models\Holiday::class;

        $query = $holidayClass::query()->where('organization_id', $organizationId);

        return $query->where(function ($builder) use ($date) {
            $builder->whereDate('holiday_date', $date->toDateString())
                ->orWhere(function ($recurring) use ($date) {
                    $recurring->where('is_recurring', true)
                        ->whereMonth('holiday_date', $date->month)
                        ->whereDay('holiday_date', $date->day);
                });
        })->exists();
    }
}
