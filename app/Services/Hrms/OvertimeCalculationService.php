<?php

namespace App\Services\Hrms;

use App\Models\AttendanceOvertimeEntry;
use App\Models\AttendanceOvertimeRule;
use App\Models\AttendanceRecord;
use App\Models\Holiday;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OvertimeCalculationService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    public function syncForRecord(AttendanceRecord $record, ?User $actor = null): ?AttendanceOvertimeEntry
    {
        $minutes = (int) ($record->overtime_minutes ?? 0);

        if ($minutes <= 0) {
            AttendanceOvertimeEntry::query()
                ->where('attendance_record_id', $record->id)
                ->whereIn('status', [
                    AttendanceOvertimeEntry::STATUS_PENDING,
                    AttendanceOvertimeEntry::STATUS_APPROVED,
                ])
                ->delete();

            return null;
        }

        $rule = $this->resolveRuleForRecord($record);
        $rounded = $this->applyRoundOff($minutes, $rule?->round_off_minutes ?? 0);
        $rounded = $this->clampMinutes($rounded, $rule);

        if ($rounded <= 0) {
            AttendanceOvertimeEntry::query()
                ->where('attendance_record_id', $record->id)
                ->whereIn('status', [
                    AttendanceOvertimeEntry::STATUS_PENDING,
                    AttendanceOvertimeEntry::STATUS_APPROVED,
                ])
                ->delete();

            return null;
        }

        $requiresApproval = (bool) ($rule?->requires_approval ?? false);
        $status = $requiresApproval
            ? AttendanceOvertimeEntry::STATUS_PENDING
            : AttendanceOvertimeEntry::STATUS_APPROVED;

        return AttendanceOvertimeEntry::query()->updateOrCreate(
            [
                'organization_id' => $record->organization_id,
                'attendance_record_id' => $record->id,
                'rule_type' => $rule?->rule_type ?? AttendanceOvertimeRule::TYPE_DAILY,
            ],
            [
                'employee_id' => $record->employee_id,
                'attendance_overtime_rule_id' => $rule?->id,
                'attendance_date' => $record->attendance_date,
                'minutes' => $rounded,
                'status' => $status,
                'reviewed_by' => $requiresApproval ? null : $actor?->id,
                'reviewed_at' => $requiresApproval ? null : now(),
            ]
        );
    }

    /**
     * @param  array{name: string, code?: string|null, rule_type: string, minimum_minutes?: int, maximum_minutes?: int|null, round_off_minutes?: int, multiplier?: float|int, requires_approval?: bool, is_active?: bool, meta?: array|null}  $data
     */
    public function createRule(array $data, User $actor): AttendanceOvertimeRule
    {
        return DB::transaction(function () use ($data, $actor): AttendanceOvertimeRule {
            $rule = AttendanceOvertimeRule::query()->create($this->normalizeRuleData($data));

            $this->auditLogger->log($rule, 'attendance_overtime_rule_created', [
                'name' => $rule->name,
                'rule_type' => $rule->rule_type,
            ], $actor);

            return $rule;
        });
    }

    /**
     * @param  array{name: string, code?: string|null, rule_type: string, minimum_minutes?: int, maximum_minutes?: int|null, round_off_minutes?: int, multiplier?: float|int, requires_approval?: bool, is_active?: bool, meta?: array|null}  $data
     */
    public function updateRule(AttendanceOvertimeRule $rule, array $data, User $actor): AttendanceOvertimeRule
    {
        return DB::transaction(function () use ($rule, $data, $actor): AttendanceOvertimeRule {
            $before = $rule->only([
                'name', 'code', 'rule_type', 'minimum_minutes', 'maximum_minutes',
                'round_off_minutes', 'multiplier', 'requires_approval', 'is_active',
            ]);

            $rule->update($this->normalizeRuleData($data));

            $this->auditLogger->log($rule, 'attendance_overtime_rule_updated', [
                'before' => $before,
                'after' => $rule->only(array_keys($before)),
            ], $actor);

            return $rule->fresh();
        });
    }

    public function activateRule(AttendanceOvertimeRule $rule, User $actor): AttendanceOvertimeRule
    {
        return DB::transaction(function () use ($rule, $actor): AttendanceOvertimeRule {
            $rule->update(['is_active' => true]);

            $this->auditLogger->log($rule, 'attendance_overtime_rule_activated', [
                'name' => $rule->name,
                'rule_type' => $rule->rule_type,
            ], $actor);

            return $rule->fresh();
        });
    }

    public function deactivateRule(AttendanceOvertimeRule $rule, User $actor): AttendanceOvertimeRule
    {
        return DB::transaction(function () use ($rule, $actor): AttendanceOvertimeRule {
            $rule->update(['is_active' => false]);

            $this->auditLogger->log($rule, 'attendance_overtime_rule_deactivated', [
                'name' => $rule->name,
                'rule_type' => $rule->rule_type,
            ], $actor);

            return $rule->fresh();
        });
    }

    /**
     * @param  array{review_notes?: string|null}  $data
     */
    public function approveEntry(AttendanceOvertimeEntry $entry, array $data, User $actor): AttendanceOvertimeEntry
    {
        return $this->approveEntries(collect([$entry]), $data, $actor)->first();
    }

    /**
     * @param  Collection<int, AttendanceOvertimeEntry>  $entries
     * @param  array{review_notes?: string|null}  $data
     * @return Collection<int, AttendanceOvertimeEntry>
     */
    public function approveEntries(Collection $entries, array $data, User $actor): Collection
    {
        return DB::transaction(function () use ($entries, $data, $actor): Collection {
            return $entries->map(function (AttendanceOvertimeEntry $entry) use ($data, $actor): AttendanceOvertimeEntry {
                if (! $entry->isPending()) {
                    throw ValidationException::withMessages([
                        'status' => __('attendance.overtime.only_pending_can_be_approved'),
                    ]);
                }

                $entry->update([
                    'status' => AttendanceOvertimeEntry::STATUS_APPROVED,
                    'reviewed_by' => $actor->id,
                    'reviewed_at' => now(),
                    'review_notes' => $data['review_notes'] ?? null,
                ]);

                $this->auditLogger->log($entry, 'overtime_entry_approved', [
                    'minutes' => $entry->minutes,
                    'attendance_record_id' => $entry->attendance_record_id,
                ], $actor);

                return $entry->fresh(['employee', 'rule', 'reviewer']);
            });
        });
    }

    /**
     * @param  array{review_notes?: string|null}  $data
     */
    public function rejectEntry(AttendanceOvertimeEntry $entry, array $data, User $actor): AttendanceOvertimeEntry
    {
        return $this->rejectEntries(collect([$entry]), $data, $actor)->first();
    }

    /**
     * @param  Collection<int, AttendanceOvertimeEntry>  $entries
     * @param  array{review_notes?: string|null}  $data
     * @return Collection<int, AttendanceOvertimeEntry>
     */
    public function rejectEntries(Collection $entries, array $data, User $actor): Collection
    {
        return DB::transaction(function () use ($entries, $data, $actor): Collection {
            return $entries->map(function (AttendanceOvertimeEntry $entry) use ($data, $actor): AttendanceOvertimeEntry {
                if (! $entry->isPending()) {
                    throw ValidationException::withMessages([
                        'status' => __('attendance.overtime.only_pending_can_be_rejected'),
                    ]);
                }

                $entry->update([
                    'status' => AttendanceOvertimeEntry::STATUS_REJECTED,
                    'reviewed_by' => $actor->id,
                    'reviewed_at' => now(),
                    'review_notes' => $data['review_notes'] ?? null,
                ]);

                $this->auditLogger->log($entry, 'overtime_entry_rejected', [
                    'minutes' => $entry->minutes,
                    'attendance_record_id' => $entry->attendance_record_id,
                ], $actor);

                return $entry->fresh(['employee', 'rule', 'reviewer']);
            });
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeRuleData(array $data): array
    {
        return [
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'rule_type' => $data['rule_type'],
            'minimum_minutes' => (int) ($data['minimum_minutes'] ?? 0),
            'maximum_minutes' => $data['maximum_minutes'] ?? null,
            'round_off_minutes' => (int) ($data['round_off_minutes'] ?? 0),
            'multiplier' => $data['multiplier'] ?? 1,
            'requires_approval' => (bool) ($data['requires_approval'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'meta' => $data['meta'] ?? null,
        ];
    }

    protected function resolveRuleForRecord(AttendanceRecord $record): ?AttendanceOvertimeRule
    {
        $date = $record->attendance_date instanceof Carbon
            ? $record->attendance_date
            : Carbon::parse($record->attendance_date);

        $isHoliday = Holiday::query()
            ->where('organization_id', $record->organization_id)
            ->whereDate('holiday_date', $date)
            ->exists();

        $weekOffDays = config('hrms.default_week_off_days', [0, 6]);
        $isWeeklyOff = in_array((int) $date->dayOfWeek, $weekOffDays, true);

        $preferredType = AttendanceOvertimeRule::TYPE_DAILY;
        if ($isHoliday) {
            $preferredType = AttendanceOvertimeRule::TYPE_HOLIDAY;
        } elseif ($isWeeklyOff) {
            $preferredType = AttendanceOvertimeRule::TYPE_WEEKLY_OFF;
        }

        $rule = AttendanceOvertimeRule::query()
            ->where('organization_id', $record->organization_id)
            ->where('is_active', true)
            ->where('rule_type', $preferredType)
            ->orderBy('id')
            ->first();

        if ($rule !== null) {
            return $rule;
        }

        return AttendanceOvertimeRule::query()
            ->where('organization_id', $record->organization_id)
            ->where('is_active', true)
            ->where('rule_type', AttendanceOvertimeRule::TYPE_DAILY)
            ->orderBy('id')
            ->first();
    }

    protected function applyRoundOff(int $minutes, int $roundOff): int
    {
        if ($roundOff <= 0) {
            return $minutes;
        }

        return (int) (round($minutes / $roundOff) * $roundOff);
    }

    protected function clampMinutes(int $minutes, ?AttendanceOvertimeRule $rule): int
    {
        if ($rule === null) {
            return $minutes;
        }

        if ($minutes < (int) $rule->minimum_minutes) {
            return 0;
        }

        if ($rule->maximum_minutes !== null) {
            $minutes = min($minutes, (int) $rule->maximum_minutes);
        }

        return $minutes;
    }
}
