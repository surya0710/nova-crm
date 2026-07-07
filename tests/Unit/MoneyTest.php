<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_balance_due_never_negative(): void
    {
        $this->assertSame(0.0, Money::balanceDue(100.0, 150.0));
    }

    public function test_balance_due_rounds_to_two_decimals(): void
    {
        $this->assertSame(0.01, Money::balanceDue(10.005, 10.0));
    }

    public function test_percentage_returns_null_for_zero_whole(): void
    {
        $this->assertNull(Money::percentage(50.0, 0.0));
    }
}
