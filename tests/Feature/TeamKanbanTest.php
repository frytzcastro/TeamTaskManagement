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

class TeamKanbanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function developer_cannot_view_team_board(): void
    {
        $developer = User::factory()->create();
        $developer->assignRole('developer');

        $this->actingAs($developer)
            ->get(route('dashboards.team'))
            ->assertForbidden();
    }

    #[Test]
    public function project_manager_sees_team_board_by_default(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');
        [$dev, $project] = $this->seedDevAndProject();

        Task::create([
            'delivery_project_id' => $project->id,
            'title' => 'Team ready',
            'assignee_id' => $dev->id,
            'status' => 'ready',
            'estimate' => 2,
        ]);

        $this->actingAs($pm)
            ->get(route('dashboards.team'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/Team')
                ->where('view', 'board')
                ->has('board', 7)
                ->has('attention')
                ->where('board', function ($board) {
                    $ready = collect($board)->firstWhere('id', 'ready');

                    return collect($ready['tasks'] ?? [])->contains(fn ($t) => $t['title'] === 'Team ready');
                })
            );
    }

    #[Test]
    public function team_board_filters_by_user_and_project(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');
        [$devA, $projectA] = $this->seedDevAndProject('A');
        $devB = User::factory()->create();
        $devB->assignRole('developer');
        $projectB = DeliveryProject::create([
            'initiative_id' => $projectA->initiative_id,
            'name' => 'Project B',
            'status' => 'active',
            'health' => 'green',
        ]);

        Task::create([
            'delivery_project_id' => $projectA->id,
            'title' => 'A task',
            'assignee_id' => $devA->id,
            'status' => 'in_progress',
            'estimate' => 1,
        ]);
        Task::create([
            'delivery_project_id' => $projectB->id,
            'title' => 'B task',
            'assignee_id' => $devB->id,
            'status' => 'in_progress',
            'estimate' => 1,
        ]);

        $this->actingAs($pm)
            ->get(route('dashboards.team', [
                'user_id' => $devA->id,
                'delivery_project_id' => $projectA->id,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('board', function ($board) {
                    $titles = collect($board)->flatMap(fn ($c) => collect($c['tasks'])->pluck('title'));

                    return $titles->contains('A task') && ! $titles->contains('B task');
                })
            );
    }

    /**
     * @return array{0: User, 1: DeliveryProject}
     */
    private function seedDevAndProject(string $suffix = ''): array
    {
        $developer = User::factory()->create();
        $developer->assignRole('developer');

        $pillar = StrategicPillar::create(['name' => 'Pillar'.$suffix, 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'Init'.$suffix, 'status' => 'active']);
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'DP'.$suffix,
            'status' => 'active',
            'health' => 'green',
        ]);

        return [$developer, $project];
    }
}
