<?php

namespace Tests\Feature;

use App\Models\DeliveryProject;
use App\Models\StrategicPillar;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HubCreateReturnFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function projectOwnedBy(User $user): DeliveryProject
    {
        $pillar = StrategicPillar::create(['name' => 'Pillar', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'Init', 'status' => 'active']);

        return DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'Hub Project',
            'type' => 'standard',
            'status' => 'active',
            'health' => 'green',
            'project_owner_id' => $user->id,
        ]);
    }

    public function test_feature_create_from_hub_passes_return_props(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');
        $project = $this->projectOwnedBy($pm);

        $this->actingAs($pm)
            ->get(route('features.create', [
                'delivery_project_id' => $project->id,
                'return_tab' => 'plan',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Features/Form')
                ->where('returnProject.id', $project->id)
                ->where('returnProject.name', 'Hub Project')
                ->where('returnTab', 'plan')
            );
    }

    public function test_feature_store_from_hub_redirects_to_plan_tab(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');
        $project = $this->projectOwnedBy($pm);

        $systemId = \App\Models\System::query()->firstOrCreate(
            ['name' => 'Test System'],
            ['type' => 'app', 'status' => 'active']
        )->id;

        $this->actingAs($pm)
            ->post(route('features.store'), [
                'system_id' => $systemId,
                'delivery_project_id' => $project->id,
                'title' => 'Hub Feature',
                'status' => 'planned',
                'priority' => 'medium',
                'return_tab' => 'plan',
                'return_project_id' => $project->id,
            ])
            ->assertRedirect(route('delivery-projects.show', [
                'deliveryProject' => $project->id,
                'tab' => 'plan',
            ]));
    }

    public function test_epic_store_redirects_to_epic_show(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');
        $this->projectOwnedBy($pm);
        $systemId = \App\Models\System::query()->firstOrCreate(
            ['name' => 'Test System'],
            ['type' => 'app', 'status' => 'active']
        )->id;

        $response = $this->actingAs($pm)
            ->post(route('epics.store'), [
                'system_id' => $systemId,
                'title' => 'Hub Epic',
                'status' => 'planned',
                'priority' => 'medium',
            ]);

        $epic = \App\Models\Epic::where('title', 'Hub Epic')->firstOrFail();
        $response->assertRedirect(route('epics.show', $epic));
    }

    public function test_task_store_from_hub_redirects_to_build_tab(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');
        $project = $this->projectOwnedBy($pm);

        $this->actingAs($pm)
            ->post(route('tasks.store'), [
                'delivery_project_id' => $project->id,
                'title' => 'Hub Task',
                'status' => 'backlog',
                'priority' => 'medium',
                'return_tab' => 'build',
            ])
            ->assertRedirect(route('delivery-projects.show', [
                'deliveryProject' => $project->id,
                'tab' => 'build',
            ]));
    }

    public function test_feature_store_without_hub_context_goes_to_show(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');
        $project = $this->projectOwnedBy($pm);

        $systemId = \App\Models\System::query()->firstOrCreate(
            ['name' => 'Test System'],
            ['type' => 'app', 'status' => 'active']
        )->id;

        $response = $this->actingAs($pm)
            ->post(route('features.store'), [
                'system_id' => $systemId,
                'delivery_project_id' => $project->id,
                'title' => 'Standalone Feature',
                'status' => 'planned',
                'priority' => 'medium',
            ]);

        $feature = \App\Models\Feature::where('title', 'Standalone Feature')->firstOrFail();
        $response->assertRedirect(route('features.show', $feature));
    }

    public function test_business_problem_store_from_hub_redirects_to_improve_tab(): void
    {
        $lead = User::factory()->create();
        $lead->assignRole('improvement_lead');
        $project = $this->projectOwnedBy($lead);
        $project->update(['type' => 'dmaic']);

        $this->actingAs($lead)
            ->post(route('business-problems.store'), [
                'initiative_id' => $project->initiative_id,
                'title' => 'From hub',
                'status' => 'open',
                'return_project_id' => $project->id,
                'return_tab' => 'improve',
            ])
            ->assertRedirect(route('delivery-projects.show', [
                'deliveryProject' => $project->id,
                'tab' => 'improve',
            ]));
    }
}
