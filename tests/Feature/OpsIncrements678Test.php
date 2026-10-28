<?php

namespace Tests\Feature;

use App\Models\DeliveryProject;
use App\Models\Feature;
use App\Models\Release;
use App\Models\Sprint;
use App\Models\StrategicPillar;
use App\Models\SupportTicket;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpsIncrements678Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_support_ticket_applies_sla_and_converts_to_bug(): void
    {
        $user = User::factory()->create();
        $user->assignRole('support');

        $this->actingAs($user)
            ->post(route('support.store'), [
                'title' => 'Help with login',
                'priority' => 'high',
                'category' => 'access_issue',
                'status' => 'open',
            ])
            ->assertRedirect();

        $ticket = SupportTicket::firstOrFail();
        $this->assertNotNull($ticket->first_response_due_at);
        $this->assertNotNull($ticket->resolve_due_at);

        $this->actingAs($user)
            ->post(route('support.convert', $ticket), ['target' => 'bug'])
            ->assertRedirect();

        $ticket->refresh();
        $this->assertSame('converted', $ticket->status);
        $this->assertNotNull($ticket->converted_bug_id);
    }

    public function test_support_cannot_double_convert(): void
    {
        $user = User::factory()->create();
        $user->assignRole('project_manager');
        $ticket = SupportTicket::create([
            'title' => 'Already done',
            'priority' => 'medium',
            'status' => 'converted',
            'reporter_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('support.convert', $ticket), ['target' => 'task'])
            ->assertStatus(422);
    }

    public function test_release_mark_deployed_syncs_task_released(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');

        $pillar = StrategicPillar::create(['name' => 'P', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'I', 'status' => 'active']);
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'DP',
            'status' => 'active',
            'health' => 'green',
        ]);
        $task = Task::create([
            'delivery_project_id' => $project->id,
            'title' => 'Ship me',
            'status' => 'ready_for_release',
        ]);
        $release = Release::create([
            'delivery_project_id' => $project->id,
            'name' => 'R1',
            'status' => 'approved',
            'environment' => 'staging',
        ]);
        $release->items()->create(['item_type' => 'task', 'item_id' => $task->id]);

        $this->actingAs($pm)
            ->post(route('releases.mark-deployed', $release))
            ->assertRedirect();

        $this->assertSame('released', $task->fresh()->status);
        $this->assertSame('deployed', $release->fresh()->status);
    }

    public function test_sprint_crud_and_task_link(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');
        $pillar = StrategicPillar::create(['name' => 'P', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'I', 'status' => 'active']);
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'DP',
            'status' => 'active',
            'health' => 'green',
        ]);

        $this->actingAs($pm)
            ->post(route('sprints.store'), [
                'delivery_project_id' => $project->id,
                'name' => 'Sprint A',
                'status' => 'active',
            ])
            ->assertRedirect();

        $sprint = Sprint::firstOrFail();
        $this->actingAs($pm)
            ->post(route('tasks.store'), [
                'delivery_project_id' => $project->id,
                'title' => 'Sprint task',
                'sprint_id' => $sprint->id,
                'status' => 'ready',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Sprint task',
            'sprint_id' => $sprint->id,
        ]);
    }

    public function test_feature_dod_checklist_persists(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');
        $pillar = StrategicPillar::create(['name' => 'P', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'I', 'status' => 'active']);
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'DP',
            'status' => 'active',
            'health' => 'green',
        ]);

        $checklist = Feature::defaultDodChecklist();
        $checklist[0]['completed'] = true;

        $system = \App\Models\System::create(['name' => 'App', 'type' => 'app', 'status' => 'active']);

        $this->actingAs($pm)
            ->post(route('features.store'), [
                'system_id' => $system->id,
                'delivery_project_id' => $project->id,
                'title' => 'DoD Feature',
                'status' => 'planned',
                'dod_checklist' => $checklist,
            ])
            ->assertRedirect();

        $feature = Feature::firstOrFail();
        $this->assertTrue((bool) ($feature->dod_checklist[0]['completed'] ?? false));
    }

    public function test_notifications_index_loads(): void
    {
        $user = User::factory()->create();
        $user->assignRole('developer');

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk();
    }

    public function test_delivery_project_hub_shows_next_steps_and_relations(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');

        $pillar = StrategicPillar::create(['name' => 'P', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'I', 'status' => 'active']);
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'type' => 'dmaic',
            'name' => 'Hub Project',
            'status' => 'active',
            'health' => 'green',
        ]);
        Task::create([
            'delivery_project_id' => $project->id,
            'title' => 'Ready item',
            'status' => 'ready_for_release',
        ]);
        Release::create([
            'delivery_project_id' => $project->id,
            'name' => 'R-hub',
            'status' => 'planned',
            'environment' => 'staging',
        ]);

        $this->actingAs($pm)
            ->get(route('delivery-projects.show', $project))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('DeliveryProjects/Show')
                ->has('nextSteps')
                ->has('hub')
                ->has('canApproveGate')
                ->has('improvementFeatures')
                ->where('hub.ready_for_release_count', 1)
                ->where('deliveryProject.releases.0.name', 'R-hub')
                ->where('nextSteps.0.key', 'start_improve')
            );
    }

    public function test_dmaic_show_redirects_to_project_hub_improve_tab(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');

        $pillar = StrategicPillar::create(['name' => 'P', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'I', 'status' => 'active']);
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'type' => 'dmaic',
            'name' => 'Redirect Hub',
            'status' => 'active',
            'health' => 'green',
        ]);

        $this->actingAs($pm)
            ->post(route('delivery-projects.dmaic.bootstrap', $project))
            ->assertRedirect(route('delivery-projects.show', ['deliveryProject' => $project->id, 'tab' => 'improve']));

        $profile = \App\Models\Dmaic\DmaicProfile::where('delivery_project_id', $project->id)->firstOrFail();

        $this->actingAs($pm)
            ->get(route('dmaic.show', $profile))
            ->assertRedirect(route('delivery-projects.show', ['deliveryProject' => $project->id, 'tab' => 'improve']));
    }

    public function test_improvement_lead_home_redirects_to_dmaic_dashboard(): void
    {
        $lead = User::factory()->create();
        $lead->assignRole('improvement_lead');

        $this->actingAs($lead)
            ->get(route('dashboard'))
            ->assertRedirect(route('dashboards.dmaic'));
    }

    public function test_project_manager_home_redirects_to_team_dashboard(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');

        $this->actingAs($pm)
            ->get(route('dashboard'))
            ->assertRedirect(route('dashboards.team'));
    }
}
