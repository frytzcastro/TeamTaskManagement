<?php

namespace Tests\Feature;

use App\Models\DeliveryProject;
use App\Models\System;
use App\Models\Feature;
use App\Models\StrategicPillar;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FeatureLinkedTasksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * @return array{0: User, 1: DeliveryProject, 2: Feature}
     */
    private function pmWithFeature(): array
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');
        $pillar = StrategicPillar::create(['name' => 'Pillar', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'Init', 'status' => 'active']);
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'Hub Project',
            'type' => 'standard',
            'status' => 'active',
            'health' => 'green',
            'project_owner_id' => $pm->id,
        ]);
        $feature = Feature::create([
            'system_id' => \App\Models\System::query()->firstOrCreate(['name' => 'Test System'], ['type' => 'app', 'status' => 'active'])->id,
            'delivery_project_id' => $project->id,
            'title' => 'Risk UI',
            'status' => 'planned',
            'priority' => 'medium',
        ]);

        return [$pm, $project, $feature];
    }

    public function test_task_create_with_feature_id_prefills_defaults(): void
    {
        [$pm, $project, $feature] = $this->pmWithFeature();

        $this->actingAs($pm)
            ->get(route('tasks.create', [
                'delivery_project_id' => $project->id,
                'feature_id' => $feature->id,
                'return_feature_id' => $feature->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tasks/Form')
                ->where('defaultFeatureId', $feature->id)
                ->where('defaultDeliveryProjectId', $project->id)
                ->where('returnFeature.id', $feature->id)
            );
    }

    public function test_task_store_with_return_feature_redirects_to_feature_show(): void
    {
        [$pm, $project, $feature] = $this->pmWithFeature();

        $this->actingAs($pm)
            ->post(route('tasks.store'), [
                'delivery_project_id' => $project->id,
                'feature_id' => $feature->id,
                'title' => 'Implement badge',
                'status' => 'backlog',
                'priority' => 'medium',
                'return_feature_id' => $feature->id,
            ])
            ->assertRedirect(route('features.show', $feature));

        $this->assertDatabaseHas('tasks', [
            'title' => 'Implement badge',
            'feature_id' => $feature->id,
            'delivery_project_id' => $project->id,
        ]);
    }

    public function test_task_store_from_hub_build_still_returns_to_hub(): void
    {
        [$pm, $project, $feature] = $this->pmWithFeature();

        $this->actingAs($pm)
            ->post(route('tasks.store'), [
                'delivery_project_id' => $project->id,
                'feature_id' => $feature->id,
                'title' => 'Hub task',
                'status' => 'backlog',
                'priority' => 'medium',
                'return_tab' => 'build',
            ])
            ->assertRedirect(route('delivery-projects.show', [
                'deliveryProject' => $project->id,
                'tab' => 'build',
            ]));
    }

    public function test_feature_show_includes_build_tasks_and_can_manage_tasks(): void
    {
        [$pm, $project, $feature] = $this->pmWithFeature();

        $this->actingAs($pm)
            ->get(route('features.show', $feature))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Features/Show')
                ->where('canManageTasks', true)
                ->has('feature.tasks')
            );
    }
}
