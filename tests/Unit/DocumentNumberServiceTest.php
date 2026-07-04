<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\DocumentNumberSequence;
use App\Services\DocumentNumberService;
use Tests\TestCase;

final class DocumentNumberServiceTest extends TestCase
{
    public function test_generates_expected_format(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 15));

        $number = (new DocumentNumberService)->generate('RF');

        $this->assertSame('RF-2026-03-001', $number);
    }

    public function test_increments_sequence_within_same_month(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 15));

        $service = new DocumentNumberService;

        $this->assertSame('PR-2026-03-001', $service->generate('PR'));
        $this->assertSame('PR-2026-03-002', $service->generate('PR'));
        $this->assertSame('PR-2026-03-003', $service->generate('PR'));
    }

    public function test_resets_sequence_on_new_month(): void
    {
        $service = new DocumentNumberService;

        $this->travelTo(now()->setDate(2026, 3, 31));
        $this->assertSame('PO-2026-03-001', $service->generate('PO'));

        $this->travelTo(now()->setDate(2026, 4, 1));
        $this->assertSame('PO-2026-04-001', $service->generate('PO'));
    }

    public function test_prefixes_have_independent_sequences(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 15));

        $service = new DocumentNumberService;

        $this->assertSame('RF-2026-03-001', $service->generate('RF'));
        $this->assertSame('PR-2026-03-001', $service->generate('PR'));
        $this->assertSame('PO-2026-03-001', $service->generate('PO'));
        $this->assertSame('RFQ-2026-03-001', $service->generate('RFQ'));
        $this->assertSame('RF-2026-03-002', $service->generate('RF'));
    }

    public function test_pads_sequence_past_three_digits_without_truncating(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 15));

        DocumentNumberSequence::create([
            'prefix' => 'RF',
            'year' => 2026,
            'month' => 3,
            'last_sequence' => 999,
        ]);

        $number = (new DocumentNumberService)->generate('RF');

        $this->assertSame('RF-2026-03-1000', $number);
    }
}
