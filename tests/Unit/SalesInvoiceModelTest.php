<?php

namespace Tests\Unit;

use App\Models\DropdownOption;
use App\Models\SalesInvoice;
use PHPUnit\Framework\TestCase;

class SalesInvoiceModelTest extends TestCase
{
    public function test_it_marks_invoice_as_unpaid_when_nothing_is_paid(): void
    {
        $this->assertSame('unpaid', SalesInvoice::resolvePaymentStatus(500, 0));
    }

    public function test_it_marks_invoice_as_partial_when_some_amount_is_paid(): void
    {
        $this->assertSame('partial', SalesInvoice::resolvePaymentStatus(500, 125));
    }

    public function test_it_marks_invoice_as_paid_when_total_is_covered(): void
    {
        $this->assertSame('paid', SalesInvoice::resolvePaymentStatus(500, 500));
    }

    public function test_it_calculates_due_amount_and_outstanding_amount(): void
    {
        $invoice = new SalesInvoice([
            'total_amount' => 550,
            'paid_amount' => 125,
        ]);

        $this->assertSame(425.0, $invoice->due_amount);
        $this->assertSame(425.0, $invoice->outstanding_amount);
    }

    public function test_it_shows_not_collected_when_payment_method_is_none(): void
    {
        $invoice = new SalesInvoice([
            'payment_method' => 'none',
        ]);

        $this->assertSame('Not collected', $invoice->payment_method_label);
    }

    public function test_it_prefers_the_loaded_payment_mode_name_for_label(): void
    {
        $invoice = new SalesInvoice([
            'payment_method' => 'cash',
        ]);

        $invoice->setRelation('paymentMode', new DropdownOption([
            'name' => 'Mobile Wallet',
        ]));

        $this->assertSame('Mobile Wallet', $invoice->payment_method_label);
    }
}
