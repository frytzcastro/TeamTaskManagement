<?php

namespace Tests\Feature;

use App\Models\DeliveryProject;
use App\Models\StrategicPillar;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MyWorkKanbanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function developer_board_view_includes_column_groups(): void
    {
        [$developer, $project] = $this->seedDeveloperWithProject();

        Task::create([
            'delivery_project_id' => $project->id,
            'title' => 'Ready work',
            'assignee_id' => $developer->id,
            'status' => 'ready',
            'estimate' => 2,
            'due_date' => now()->addDay(),
        ]);

        Task::create([
            'delivery_project_id' => $project->id,
            'title' => 'Old done',
            'assignee_id' => $developer->id,
            'status' => 'done',
            'completed_at' => now()->subDays(40),
            'due_date' => now()->subDays(41),
        ]);

        Task::create([
            'delivery_project_id' => $project->id,
            'title' => 'Recent done',
            'assignee_id' => $developer->id,
            'status' => 'done',
            'completed_at' => now()->subDays(2),
            'due_date' => now()->subDays(3),
        ]);

        $this->actingAs($developer)
            ->get(route('dashboards.developer', ['view' => 'board']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/Developer')
                ->where('view', 'board')
                ->has('board', 7)
                ->where('board', function ($board) {
                    $byId = collect($board)->keyBy('id');
                    $readyTitles = collect($byId['ready']['tasks'])->pluck('title');
                    $doneTitles = collect($byId['done']['tasks'])->pluck('title');

                    return $readyTitles->contains('Ready work')
                        && $doneTitles->contains('Recent done')
                        && ! $doneTitles->contains('Old done');
                })
            );
    }

    #[Test]
    public function patch_status_requires_estimate_for_in_progress(): void
    {
        [$developer, $project] = $this->seedDeveloperWithProject();
        $task = Task::create([
            'delivery_project_id' => $project->id,
            'title' => 'Needs estimate',
            'assignee_id' => $developer->id,
            'status' => 'ready',
        ]);

        $this->actingAs($developer)
            ->patchJson(route('tasks.status.update', $task), [
                'status' => 'in_progress',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('estimate');

        $this->assertSame('ready', $task->fresh()->status);
    }

    #[Test]
    public function patch_status_with_estimate_sets_started_at(): void
    {
        [$developer, $project] = $this->seedDeveloperWithProject();
        $task = Task::create([
            'delivery_project_id' => $project->id,
            'title' => 'Startable',
            'assignee_id' => $developer->id,
            'status' => 'ready',
        ]);

        $this->actingAs($developer)
            ->patchJson(route('tasks.status.update', $task), [
                'status' => 'in_progress',
                'estimate' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('task.status', 'in_progress');

        $task->refresh();
        $this->assertSame('in_progress', $task->status);
        $this->assertNotNull($task->started_at);
        $this->assertEquals(3, (float) $task->estimate);
    }

    #[Test]
    public function cannot_patch_another_users_task_status(): void
    {
        [$developer, $project] = $this->seedDeveloperWithProject();
        $other = User::factory()->create();
        $other->assignRole('developer');

        $task = Task::create([
            'delivery_project_id' => $project->id,
            'title' => 'Not yours',
            'assignee_id' => $developer->id,
            'status' => 'ready',
            'estimate' => 2,
        ]);

        $this->actingAs($other)
            ->patchJson(route('tasks.status.update', $task), [
                'status' => 'in_progress',
            ])
            ->assertForbidden();
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
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'DP',
            'status' => 'active',
            'health' => 'green',
        ]);

        return [$developer, $project];
    }
}
