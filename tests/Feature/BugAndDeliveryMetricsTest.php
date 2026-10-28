<?php

namespace Tests\Feature;

use App\Models\Bug;
use App\Models\DeliveryProject;
use App\Models\StrategicPillar;
use App\Models\Task;
use App\Models\User;
use App\Services\DeliveryMetricsService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BugAndDeliveryMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_developer_can_report_bug(): void
    {
        $developer = User::factory()->create();
        $developer->assignRole('developer');

        $this->actingAs($developer)
            ->post(route('bugs.store'), [
                'title' => 'Login spinner hangs',
                'severity' => 'high',
                'environment' => 'staging',
                'status' => 'reported',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bugs', [
            'title' => 'Login spinner hangs',
            'reporter_id' => $developer->id,
        ]);
    }

    public function test_developer_cannot_delete_bug(): void
    {
        $developer = User::factory()->create();
        $developer->assignRole('developer');

        $bug = Bug::create([
            'title' => 'Temp',
            'status' => 'reported',
            'reporter_id' => $developer->id,
        ]);

        $this->actingAs($developer)
            ->delete(route('bugs.destroy', $bug))
            ->assertForbidden();
    }

    public function test_project_manager_can_manage_bugs(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');

        $this->actingAs($pm)
            ->post(route('bugs.store'), [
                'title' => 'PM filed bug',
                'severity' => 'medium',
                'environment' => 'development',
            ])
            ->assertRedirect();

        $bug = Bug::where('title', 'PM filed bug')->firstOrFail();

        $this->actingAs($pm)
            ->delete(route('bugs.destroy', $bug))
            ->assertRedirect(route('bugs.index'));
    }

    public function test_reopen_increments_reopened_count(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');

        $bug = Bug::create([
            'title' => 'Flaky bug',
            'status' => 'resolved',
            'reporter_id' => $pm->id,
            'resolved_at' => now(),
            'reopened_count' => 0,
        ]);

        $this->actingAs($pm)
            ->put(route('bugs.update', $bug), [
                'title' => 'Flaky bug',
                'status' => 'reopened',
            ])
            ->assertRedirect(route('bugs.show', $bug));

        $this->assertDatabaseHas('bugs', [
            'id' => $bug->id,
            'status' => 'reopened',
            'reopened_count' => 1,
        ]);
    }

    public function test_task_status_sets_started_at(): void
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
        $task = Task::create([
            'delivery_project_id' => $deliveryProject->id,
            'title' => 'Work',
            'assignee_id' => $developer->id,
            'status' => 'ready',
        ]);

        $this->actingAs($developer)
            ->put(route('tasks.update', $task), [
                'status' => 'in_progress',
                'estimate' => 3,
            ])
            ->assertRedirect();

        $task->refresh();
        $this->assertNotNull($task->started_at);
    }

    public function test_delivery_metrics_smoke(): void
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

        Task::create([
            'delivery_project_id' => $deliveryProject->id,
            'title' => 'Done task',
            'assignee_id' => $developer->id,
            'status' => 'done',
            'due_date' => now()->subDay(),
            'started_at' => now()->subDays(3),
            'completed_at' => now()->subDay(),
        ]);

        Task::create([
            'delivery_project_id' => $deliveryProject->id,
            'title' => 'Overdue task',
            'assignee_id' => $developer->id,
            'status' => 'in_progress',
            'due_date' => now()->subDays(2),
            'started_at' => now()->subDays(5),
        ]);

        Bug::create([
            'title' => 'Critical prod',
            'severity' => 'critical',
            'environment' => 'production',
            'status' => 'assigned',
            'assignee_id' => $developer->id,
        ]);

        $metrics = app(DeliveryMetricsService::class)->summarize(null, $developer);

        $this->assertSame(1, $metrics['delivery']['tasks_completed']);
        $this->assertSame(1, $metrics['delivery']['tasks_overdue']);
        $this->assertSame(1, $metrics['quality']['open_critical']);
        $this->assertSame(1, $metrics['quality']['production_bugs']);
    }

    public function test_team_dashboard_requires_permission(): void
    {
        $developer = User::factory()->create();
        $developer->assignRole('developer');

        $this->actingAs($developer)
            ->get(route('dashboards.team'))
            ->assertForbidden();
    }

    public function test_executive_can_view_team_dashboard(): void
    {
        $exec = User::factory()->create();
        $exec->assignRole('executive');

        $this->actingAs($exec)
            ->get(route('dashboards.team'))
            ->assertOk();
    }
}
