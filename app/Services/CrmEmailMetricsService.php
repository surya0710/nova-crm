<?php

namespace App\Services;

use App\Models\CrmEmailMessage;
use App\Models\Opportunity;
use App\Models\Organization;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class CrmEmailMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(?Organization $organization, mixed $from = null, mixed $to = null): array
    {
        $fromAt = $this->date($from)?->startOfDay() ?? now()->subDays(30)->startOfDay();
        $toAt = $this->date($to)?->endOfDay() ?? now()->endOfDay();

        $base = CrmEmailMessage::query()->whereBetween('created_at', [$fromAt, $toAt]);

        $counts = [
            'queued' => (clone $base)->whereIn('status', ['queued', 'sending'])->count(),
            'sent' => (clone $base)->whereIn('status', ['sent', 'delivered', 'bounced'])->count(),
            'delivered' => (clone $base)->where('status', 'delivered')->count(),
            'failed' => (clone $base)->where('status', 'failed')->count(),
            'bounced' => (clone $base)->where('status', 'bounced')->count(),
        ];

        $attempted = $counts['sent'] + $counts['failed'];
        $tracked = $counts['delivered'] + $counts['bounced'] + $counts['failed'];

        return [
            'from' => $fromAt->toDateString(),
            'to' => $toAt->toDateString(),
            'emails_queued' => $counts['queued'],
            'emails_sent' => $counts['sent'],
            'emails_delivered' => $counts['delivered'],
            'emails_failed' => $counts['failed'],
            'emails_bounced' => $counts['bounced'],
            'delivery_rate' => $this->rate($counts['delivered'], max($tracked, $counts['sent'])),
            'failure_rate' => $this->rate($counts['failed'] + $counts['bounced'], max($attempted, 1)),
            'by_salesperson' => (clone $base)
                ->selectRaw('sent_by, COUNT(*) as total')
                ->whereNotNull('sent_by')
                ->groupBy('sent_by')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(fn ($row) => ['user_id' => $row->sent_by, 'total' => (int) $row->total])
                ->all(),
            'by_customer' => (clone $base)
                ->selectRaw('customer_id, COUNT(*) as total')
                ->whereNotNull('customer_id')
                ->groupBy('customer_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(fn ($row) => ['customer_id' => $row->customer_id, 'total' => (int) $row->total])
                ->all(),
            'by_template' => (clone $base)
                ->selectRaw('template_id, COUNT(*) as total')
                ->whereNotNull('template_id')
                ->groupBy('template_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(fn ($row) => ['template_id' => $row->template_id, 'total' => (int) $row->total])
                ->all(),
            'by_date' => (clone $base)
                ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
                ->groupBy('day')
                ->orderBy('day')
                ->get()
                ->map(fn ($row) => ['date' => $row->day, 'total' => (int) $row->total])
                ->all(),
            'by_opportunity' => (clone $base)
                ->where('related_type', (new Opportunity)->getMorphClass())
                ->selectRaw('related_id, COUNT(*) as total')
                ->groupBy('related_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(fn ($row) => ['opportunity_id' => $row->related_id, 'total' => (int) $row->total])
                ->all(),
        ];
    }

    protected function date(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value);
        }

        return null;
    }

    protected function rate(int $numerator, int $denominator): float
    {
        if ($denominator < 1) {
            return 0.0;
        }

        return round(($numerator / $denominator) * 100, 1);
    }
}
