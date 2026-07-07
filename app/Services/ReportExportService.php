<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Organization;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function __construct(protected RevenueService $revenue) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportOutstandingInvoices(Organization $organization, array $filters = []): StreamedResponse
    {
        $invoices = $this->revenue->outstandingInvoices($organization, $filters);
        $filename = 'outstanding-invoices-'.now()->format('Y-m-d').'.csv';

        return $this->streamCsv($filename, function ($handle) use ($invoices) {
            fputcsv($handle, [
                __('Invoice #'),
                __('Customer'),
                __('Issue Date'),
                __('Due Date'),
                __('Status'),
                __('Total'),
                __('Paid'),
                __('Balance Due'),
                __('Days Overdue'),
            ]);

            $today = now()->startOfDay();

            foreach ($invoices as $invoice) {
                $balance = max(0, (float) $invoice->total - (float) $invoice->amount_paid);
                $dueDate = $invoice->due_date?->startOfDay() ?? $today;
                $daysOverdue = $dueDate->lte($today) ? (int) $dueDate->diffInDays($today) : 0;

                fputcsv($handle, [
                    $invoice->number,
                    $invoice->customer?->display_name ?? '',
                    $invoice->issue_date?->format('Y-m-d') ?? '',
                    $invoice->due_date?->format('Y-m-d') ?? '',
                    $invoice->status_label,
                    number_format((float) $invoice->total, 2, '.', ''),
                    number_format((float) $invoice->amount_paid, 2, '.', ''),
                    number_format($balance, 2, '.', ''),
                    $daysOverdue,
                ]);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportRevenueReport(Organization $organization, array $filters = []): StreamedResponse
    {
        $report = $this->revenue->compileFinanceReport($organization, $filters);
        $filename = 'revenue-report-'.now()->format('Y-m-d').'.csv';

        return $this->streamCsv($filename, function ($handle) use ($report) {
            fputcsv($handle, [__('Metric'), __('Value')]);
            fputcsv($handle, [__('Outstanding Receivables'), number_format($report['outstanding_receivables'], 2, '.', '')]);
            fputcsv($handle, [__('Total Paid'), number_format($report['total_paid'], 2, '.', '')]);
            fputcsv($handle, [__('Total Invoiced'), number_format($report['total_invoiced'], 2, '.', '')]);
            fputcsv($handle, [__('Collected This Month'), number_format($report['collected_this_month'], 2, '.', '')]);
            fputcsv($handle, [__('Collection Rate (%)'), $report['collection']['collection_rate'] ?? '']);
            fputcsv($handle, [__('Average Days to Payment'), $report['collection']['average_days_to_payment'] ?? '']);

            fputcsv($handle, []);
            fputcsv($handle, [__('Revenue by Month')]);
            fputcsv($handle, [__('Month'), __('Total')]);
            foreach ($report['revenue_by_month'] as $row) {
                fputcsv($handle, [$row['label'], number_format($row['total'], 2, '.', '')]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [__('Revenue by Customer')]);
            fputcsv($handle, [__('Customer'), __('Total'), __('Payments')]);
            foreach ($report['revenue_by_customer'] as $row) {
                fputcsv($handle, [$row['name'], number_format($row['total'], 2, '.', ''), $row['payment_count']]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [__('Invoice Aging')]);
            fputcsv($handle, [__('Bucket'), __('Total'), __('Count')]);
            foreach ($report['aging'] as $bucket) {
                fputcsv($handle, [$bucket['label'], number_format($bucket['total'], 2, '.', ''), $bucket['count']]);
            }
        });
    }

    public function exportCustomerStatement(Customer $customer): StreamedResponse
    {
        $statement = $this->revenue->customerStatement($customer);
        $filename = 'customer-statement-'.$customer->id.'-'.now()->format('Y-m-d').'.csv';

        return $this->streamCsv($filename, function ($handle) use ($statement, $customer) {
            fputcsv($handle, [__('Customer Statement')]);
            fputcsv($handle, [__('Customer'), $customer->display_name]);
            fputcsv($handle, [__('Total Invoiced'), number_format($statement['total_invoiced'], 2, '.', '')]);
            fputcsv($handle, [__('Total Paid'), number_format($statement['total_paid'], 2, '.', '')]);
            fputcsv($handle, [__('Balance Due'), number_format($statement['balance_due'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, [__('Date'), __('Type'), __('Reference'), __('Debit'), __('Credit'), __('Balance')]);

            foreach ($statement['ledger'] as $entry) {
                fputcsv($handle, [
                    $entry['date']?->format('Y-m-d') ?? '',
                    $entry['type'] === 'invoice' ? __('Invoice') : __('Payment'),
                    $entry['number'],
                    $entry['debit'] > 0 ? number_format($entry['debit'], 2, '.', '') : '',
                    $entry['credit'] > 0 ? number_format($entry['credit'], 2, '.', '') : '',
                    number_format($entry['balance'], 2, '.', ''),
                ]);
            }
        });
    }

    /**
     * @param  callable(resource): void  $writer
     */
    protected function streamCsv(string $filename, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($writer) {
            $handle = fopen('php://output', 'w');
            $writer($handle);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
