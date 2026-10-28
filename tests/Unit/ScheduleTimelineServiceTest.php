<?php

namespace Tests\Unit;

use App\Services\ScheduleTimelineService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScheduleTimelineServiceTest extends TestCase
{
    private ScheduleTimelineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ScheduleTimelineService::class);
    }

    #[Test]
    public function adjacent_windows_do_not_overlap(): void
    {
        $this->assertFalse($this->service->datesOverlap('2026-10-01', '2026-10-05', '2026-10-06', '2026-10-10'));
    }

    #[Test]
    public function same_day_windows_overlap(): void
    {
        $this->assertTrue($this->service->datesOverlap('2026-10-01', '2026-10-05', '2026-10-05', '2026-10-08'));
    }

    #[Test]
    public function different_assignees_are_not_flagged_together(): void
    {
        $a = new \App\Models\Task([
            'assignee_id' => '11111111-1111-1111-1111-111111111111',
            'start_date' => '2026-10-01',
            'due_date' => '2026-10-10',
            'status' => 'in_progress',
        ]);
        $a->id = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
        $b = new \App\Models\Task([
            'assignee_id' => '22222222-2222-2222-2222-222222222222',
            'start_date' => '2026-10-01',
            'due_date' => '2026-10-10',
            'status' => 'in_progress',
        ]);
        $b->id = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';

        $this->assertTrue($this->service->detectOverlaps(Collection::make([$a, $b]))->isEmpty());
    }

    #[Test]
    public function same_assignee_overlapping_tasks_are_detected(): void
    {
        $assignee = '11111111-1111-1111-1111-111111111111';
        $a = new \App\Models\Task([
            'assignee_id' => $assignee,
            'start_date' => '2026-10-01',
            'due_date' => '2026-10-10',
            'status' => 'in_progress',
        ]);
        $a->id = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
        $b = new \App\Models\Task([
            'assignee_id' => $assignee,
            'start_date' => '2026-10-05',
            'due_date' => '2026-10-15',
            'status' => 'in_progress',
        ]);
        $b->id = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';

        $overlaps = $this->service->detectOverlaps(Collection::make([$a, $b]));
        $this->assertTrue($overlaps->has('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'));
        $this->assertTrue($overlaps->has('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb'));
    }

    #[Test]
    public function slip_when_rolled_end_after_planned(): void
    {
        $this->assertTrue($this->service->hasSlip('2026-09-01', '2026-10-15'));
        $this->assertFalse($this->service->hasSlip('2026-10-15', '2026-10-15'));
        $this->assertFalse($this->service->hasSlip('2026-10-15', '2026-10-01'));
        $this->assertFalse($this->service->hasSlip(null, '2026-10-01'));
    }
}
