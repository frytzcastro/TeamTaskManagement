<?php

namespace Tests\Feature;

use App\Models\DeliveryProject;
use App\Models\System;
use App\Models\Epic;
use App\Models\Feature;
use App\Models\StrategicPillar;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScheduleGanttTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function developer_cannot_view_portfolio_schedule(): void
    {
        $developer = User::factory()->create();
        $developer->assignRole('developer');

        $this->actingAs($developer)
            ->get(route('schedule.index'))
            ->assertForbidden();
    }

    #[Test]
    public function project_manager_can_view_portfolio_schedule(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');

        $this->actingAs($pm)
            ->get(route('schedule.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Schedule/Index')
                ->has('schedule.bars')
                ->has('schedule.undated')
                ->has('schedule.conflicts')
                ->has('schedule.window')
            );
    }

    #[Test]
    public function project_hub_includes_schedule_tree_and_undated_gaps(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');

        $pillar = StrategicPillar::create(['name' => 'Pillar', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'Init', 'status' => 'active']);
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'DP',
            'status' => 'active',
            'health' => 'green',
            'start_date' => '2026-08-01',
            'target_completion' => '2026-09-01',
        ]);
        $epic = Epic::create([
            'system_id' => \App\Models\System::query()->firstOrCreate(['name' => 'Test System'], ['type' => 'app', 'status' => 'active'])->id,
            // project: $project
            'title' => 'Epic',
            'status' => 'planned',
            'due_date' => '2026-09-01',
        ]);
        $feature = Feature::create([
            'system_id' => \App\Models\System::query()->firstOrCreate(['name' => 'Test System'], ['type' => 'app', 'status' => 'active'])->id,
            'delivery_project_id' => $project->id,
            'epic_id' => $epic->id,
            'title' => 'Feature',
            'status' => 'planned',
            'due_date' => '2026-09-01',
        ]);
        Task::create([
            'delivery_project_id' => $project->id,
            'feature_id' => $feature->id,
            'epic_id' => $epic->id,
            'title' => 'Dated task',
            'status' => 'ready',
            'assignee_id' => $pm->id,
            'start_date' => '2026-08-10',
            'due_date' => '2026-10-01',
            'estimate' => 3,
        ]);
        Task::create([
            'delivery_project_id' => $project->id,
            'feature_id' => $feature->id,
            'epic_id' => $epic->id,
            'title' => 'Undated task',
            'status' => 'backlog',
            'assignee_id' => $pm->id,
        ]);

        $this->actingAs($pm)
            ->get(route('delivery-projects.show', $project))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('DeliveryProjects/Show')
                ->has('schedule.bars')
                ->has('schedule.undated')
                ->where('schedule.undated', fn ($undated) => collect($undated)->contains(fn ($u) => $u['title'] === 'Undated task'))
                ->where('schedule.bars', fn ($bars) => collect($bars)->contains(fn ($b) => ($b['type'] ?? null) === 'task' && ($b['title'] ?? null) === 'Dated task'))
                ->where('schedule.bars', fn ($bars) => collect($bars)->contains(fn ($b) => ($b['type'] ?? null) === 'feature' && ($b['slip'] ?? false) === true))
            );
    }

    #[Test]
    public function portfolio_expand_project_returns_task_bars(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');

        $pillar = StrategicPillar::create(['name' => 'Pillar', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'Init', 'status' => 'active']);
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'DP',
            'status' => 'active',
            'health' => 'green',
        ]);
        Task::create([
            'delivery_project_id' => $project->id,
            'title' => 'Leaf',
            'status' => 'ready',
            'start_date' => '2026-07-01',
            'due_date' => '2026-07-15',
            'assignee_id' => $pm->id,
        ]);

        $this->actingAs($pm)
            ->get(route('schedule.index', ['project_id' => $project->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('schedule.bars', fn ($bars) => collect($bars)->contains(fn ($b) => ($b['title'] ?? null) === 'Leaf'))
            );
    }
}
