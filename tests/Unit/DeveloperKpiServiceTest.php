<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Services\DeveloperKpiService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeveloperKpiServiceTest extends TestCase
{
    private DeveloperKpiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DeveloperKpiService;
        Carbon::setTestNow('2026-10-03 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function on_time_delivery_scores_100(): void
    {
        $task = new Task([
            'status' => 'done',
            'due_date' => '2026-10-01',
            'completed_at' => '2026-10-01 15:00:00',
        ]);

        $this->assertSame(100.0, $this->service->deliveryScoreForTask($task));
    }

    #[Test]
    public function two_weeks_early_caps_toward_120(): void
    {
        $task = new Task([
            'status' => 'done',
            'due_date' => '2026-10-01',
            'completed_at' => '2026-09-17 15:00:00',
        ]);

        // 14 days early ≈ 2 weeks * (100/26) ≈ 7.7 → 107.7
        $score = $this->service->deliveryScoreForTask($task);
        $this->assertSame(107.7, $score);
        $this->assertLessThanOrEqual(120.0, $score);
    }

    #[Test]
    public function early_finish_caps_at_120(): void
    {
        $task = new Task([
            'status' => 'done',
            'due_date' => '2026-10-01',
            'completed_at' => '2025-01-01 15:00:00',
        ]);

        $this->assertSame(120.0, $this->service->deliveryScoreForTask($task));
    }

    #[Test]
    public function twenty_six_weeks_late_scores_zero(): void
    {
        $task = new Task([
            'status' => 'done',
            'due_date' => '2026-04-03',
            'completed_at' => '2026-10-03 15:00:00',
        ]);

        // 183 days ≈ 26.14 weeks → near 0
        $this->assertSame(0.0, $this->service->deliveryScoreForTask($task));
    }

    #[Test]
    public function commitment_score_uses_estimate_over_actual(): void
    {
        $task = new Task([
            'estimate' => 8,
            'started_at' => '2026-09-21', // Mon
            'completed_at' => '2026-10-02', // Fri → 10 working days inclusive
        ]);

        $actual = $this->service->actualWorkingDays($task);
        $this->assertSame(10.0, $actual);
        $this->assertSame(80.0, min(120.0, round(100 * 8 / $actual, 1)));
    }
}
