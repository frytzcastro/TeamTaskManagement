<?php

namespace Tests\Feature;

use App\Models\DeliveryProject;
use App\Models\System;
use App\Models\Epic;
use App\Models\Feature;
use App\Models\StrategicPillar;
use App\Models\Task;
use App\Models\User;
use App\Services\DeveloperKpiService;
use App\Services\ScheduleRollupService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeveloperKpiAndScheduleRollupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function task_without_estimate_cannot_enter_in_progress(): void
    {
        [$developer, $deliveryProject] = $this->seedDeveloperWithProject();

        $task = Task::create([
            'delivery_project_id' => $deliveryProject->id,
            'title' => 'Needs estimate',
            'assignee_id' => $developer->id,
            'status' => 'ready',
        ]);

        $this->actingAs($developer)
            ->from(route('tasks.show', $task))
            ->put(route('tasks.update', $task), [
                'status' => 'in_progress',
            ])
            ->assertRedirect(route('tasks.show', $task))
            ->assertSessionHasErrors('estimate');

        $this->assertSame('ready', $task->fresh()->status);
    }

    #[Test]
    public function updating_task_dates_refreshes_feature_epic_project_envelopes(): void
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
        $task = Task::create([
            'delivery_project_id' => $project->id,
            'feature_id' => $feature->id,
            'epic_id' => $epic->id,
            'title' => 'Leaf',
            'status' => 'ready',
            'assignee_id' => $pm->id,
            'estimate' => 2,
        ]);

        $this->actingAs($pm)
            ->put(route('tasks.update', $task), [
                'delivery_project_id' => $project->id,
                'feature_id' => $feature->id,
                'epic_id' => $epic->id,
                'title' => 'Leaf',
                'status' => 'ready',
                'estimate' => 2,
                'start_date' => '2026-08-01',
                'due_date' => '2026-10-15',
                'assignee_id' => $pm->id,
            ])
            ->assertRedirect();

        $feature->refresh();
        $epic->refresh();
        $project->refresh();

        $this->assertSame('2026-08-01', optional($feature->rolled_start_date)?->toDateString());
        $this->assertSame('2026-10-15', optional($feature->rolled_due_date)?->toDateString());
        $this->assertSame('2026-08-01', optional($epic->rolled_start_date)?->toDateString());
        $this->assertSame('2026-10-15', optional($epic->rolled_due_date)?->toDateString());
        $this->assertSame('2026-08-01', optional($project->rolled_start_date)?->toDateString());
        $this->assertSame('2026-10-15', optional($project->rolled_due_date)?->toDateString());
        $this->assertTrue($feature->scheduleBeyondPlanned());
        $this->assertTrue($project->scheduleBeyondPlanned());
    }

    #[Test]
    public function schedule_rollup_service_computes_envelope_directly(): void
    {
        [$developer, $deliveryProject] = $this->seedDeveloperWithProject();
        $feature = Feature::create([
            'system_id' => \App\Models\System::query()->firstOrCreate(['name' => 'Test System'], ['type' => 'app', 'status' => 'active'])->id,
            'delivery_project_id' => $deliveryProject->id,
            'title' => 'F',
            'status' => 'planned',
        ]);
        Task::create([
            'delivery_project_id' => $deliveryProject->id,
            'feature_id' => $feature->id,
            'title' => 'A',
            'status' => 'ready',
            'start_date' => '2026-07-01',
            'due_date' => '2026-07-10',
        ]);
        Task::create([
            'delivery_project_id' => $deliveryProject->id,
            'feature_id' => $feature->id,
            'title' => 'B',
            'status' => 'ready',
            'start_date' => '2026-07-05',
            'due_date' => '2026-07-20',
        ]);

        app(ScheduleRollupService::class)->refreshFeature($feature->id);

        $feature->refresh();
        $deliveryProject->refresh();
        $this->assertSame('2026-07-01', optional($feature->rolled_start_date)?->toDateString());
        $this->assertSame('2026-07-20', optional($feature->rolled_due_date)?->toDateString());
        $this->assertSame('2026-07-01', optional($deliveryProject->rolled_start_date)?->toDateString());
        $this->assertSame('2026-07-20', optional($deliveryProject->rolled_due_date)?->toDateString());
    }

    #[Test]
    public function developer_dashboard_returns_three_kpi_payloads(): void
    {
        [$developer, $deliveryProject] = $this->seedDeveloperWithProject();

        Task::create([
            'delivery_project_id' => $deliveryProject->id,
            'title' => 'Done on time',
            'assignee_id' => $developer->id,
            'status' => 'done',
            'estimate' => 5,
            'due_date' => now()->subDays(2),
            'started_at' => now()->subDays(7),
            'completed_at' => now()->subDays(2),
        ]);

        $this->actingAs($developer)
            ->get(route('dashboards.developer'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/Developer')
                ->has('kpis.owned_delivery')
                ->has('kpis.commitment')
                ->has('kpis.quality')
                ->has('kpis.gaps')
                ->has('filters.from')
                ->has('filters.to')
            );
    }

    #[Test]
    public function commitment_score_averages_completed_work(): void
    {
        [$developer, $deliveryProject] = $this->seedDeveloperWithProject();

        Task::create([
            'delivery_project_id' => $deliveryProject->id,
            'title' => 'Estimate 8 actual 10',
            'assignee_id' => $developer->id,
            'status' => 'done',
            'estimate' => 8,
            'started_at' => '2026-09-21',
            'completed_at' => '2026-10-02',
            'due_date' => '2026-10-02',
        ]);

        $result = app(DeveloperKpiService::class)->scoreCommitment(
            $developer,
            now()->parse('2026-09-01')->startOfDay(),
            now()->parse('2026-10-10')->endOfDay(),
        );

        $this->assertSame(80.0, $result['score']);
        $this->assertSame(1, $result['scored_count']);
    }

    /**
     * @return array{0: User, 1: DeliveryProject}
     */
    private function seedDeveloperWithProject(): array
    {
        $developer = User::factory()->create();
        $developer->assignRole('developer');

        $pillar = StrategicPillar::create(['name' => 'Pillar', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'Init', 'status' => 'active']);
        $deliveryProject = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'DP',
            'status' => 'active',
            'health' => 'green',
        ]);

        return [$developer, $deliveryProject];
    }
}
