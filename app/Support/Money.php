<?php

namespace App\Support;

/**
 * Monetary value helpers for Konnect Nex.
 *
 * Rounding strategy (all amounts stored as decimal 15,2):
 *
 * - Line items: round each intermediate (subtotal, discount, tax, line total) to 2 dp before summing.
 * - Document totals: sum of rounded line amounts (see Invoice/Quotation calculation services).
 * - amount_paid: round(sum(payment amounts), 2) on the invoice.
 * - balance_due: max(0, round(invoice.total − invoice.amount_paid, 2)).
 * - Aggregates (reports): SQL SUM where possible; PHP-side sums use round(..., 2).
 * - Percentages (collection rate, win rate): round to 1 dp.
 * - Averages: round to 2 dp.
 */
final class Money
{
    public static function round(float $amount, int $precision = 2): float
    {
        return round($amount, $precision);
    }

    public static function balanceDue(float $total, float $amountPaid): float
    {
        return max(0, self::round($total - $amountPaid));
    }

    public static function percentage(float $part, float $whole, int $precision = 1): ?float
    {
        if ($whole <= 0) {
            return null;
        }

        return self::round(($part / $whole) * 100, $precision);
    }
}
