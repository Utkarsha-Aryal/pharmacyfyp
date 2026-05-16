<?php

namespace Tests\Unit;

use App\Models\Purchase;
use PHPUnit\Framework\TestCase;

class PurchaseModelTest extends TestCase
{
    public function test_it_marks_purchase_as_unpaid_when_paid_amount_is_zero(): void
    {
        $this->assertSame('unpaid', Purchase::resolvePaymentStatus(700, 0));
    }

    public function test_it_marks_purchase_as_partial_when_paid_amount_is_less_than_total(): void
    {
        $this->assertSame('partial', Purchase::resolvePaymentStatus(700, 200));
    }

    public function test_it_marks_purchase_as_paid_when_total_is_fully_paid(): void
    {
        $this->assertSame('paid', Purchase::resolvePaymentStatus(700, 700));
    }

    public function test_it_calculates_due_amount_and_outstanding_amount(): void
    {
        $purchase = new Purchase([
            'grand_total' => 900,
            'paid_amount' => 260,
        ]);

        $this->assertSame(640.0, $purchase->due_amount);
        $this->assertSame(640.0, $purchase->outstanding_amount);
    }
}
