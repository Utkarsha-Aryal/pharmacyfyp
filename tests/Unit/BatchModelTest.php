<?php

namespace Tests\Unit;

use App\Models\Batch;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class BatchModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-16 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_parses_a_valid_expiry_date(): void
    {
        $expiryDate = Batch::makeExpiryDate('2026-06-01');

        $this->assertSame('2026-06-01', $expiryDate?->format('Y-m-d'));
    }

    public function test_it_returns_null_for_invalid_expiry_date(): void
    {
        $this->assertNull(Batch::makeExpiryDate('2026-14-40'));
    }

    public function test_it_marks_expired_batch_as_danger(): void
    {
        $batch = new Batch([
            'expiry_date' => '2026-05-10',
        ]);

        $this->assertSame(-6, $batch->days_remaining);
        $this->assertSame('danger', $batch->row_state);
    }

    public function test_it_marks_near_expiry_batch_as_warning(): void
    {
        $batch = new Batch([
            'expiry_date' => '2026-05-28',
        ]);

        $this->assertSame(12, $batch->days_remaining);
        $this->assertSame('warning', $batch->row_state);
    }

    public function test_it_marks_safe_batch_as_success(): void
    {
        $batch = new Batch([
            'expiry_date' => '2026-07-10',
        ]);

        $this->assertSame('success', $batch->row_state);
    }
}
