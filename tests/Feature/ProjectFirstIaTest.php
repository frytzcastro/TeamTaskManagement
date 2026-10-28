<?php

namespace Tests\Feature;

use App\Models\BusinessProblem;
use App\Models\DeliveryProject;
use App\Models\Dmaic\DmaicProfile;
use App\Models\StrategicPillar;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectFirstIaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_portfolio_includes_delivery_projects_under_initiatives(): void
    {
        $po = User::factory()->create();
        $po->assignRole('product_owner');

        $pillar = StrategicPillar::create(['name' => 'Pillar', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'Init', 'status' => 'active']);
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'Risk Engine',
            'type' => 'standard',
            'status' => 'active',
            'health' => 'green',
            'project_owner_id' => $po->id,
        ]);

        $this->actingAs($po)
            ->get(route('portfolio.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portfolio/Index')
                ->has('pillars', 1)
                ->has('pillars.0.initiatives', 1)
                ->where('pillars.0.initiatives.0.delivery_projects.0.id', $project->id)
                ->where('pillars.0.initiatives.0.delivery_projects.0.name', 'Risk Engine')
                ->where('summary.pillars', 1)
                ->where('summary.initiatives', 1)
                ->where('summary.delivery_projects', 1)
            );
    }

    public function test_business_problem_show_includes_linked_dmaic_project(): void
    {
        $lead = User::factory()->create();
        $lead->assignRole('improvement_lead');

        $pillar = StrategicPillar::create(['name' => 'Pillar', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'Init', 'status' => 'active']);
        $problem = BusinessProblem::create([
            'initiative_id' => $initiative->id,
            'title' => 'Late cancellations',
            'status' => 'open',
            'owner_id' => $lead->id,
        ]);
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'Cancellation DMAIC',
            'type' => 'dmaic',
            'status' => 'active',
            'health' => 'green',
            'project_owner_id' => $lead->id,
        ]);
        DmaicProfile::create([
            'delivery_project_id' => $project->id,
            'business_problem_id' => $problem->id,
            'current_phase' => 'DEFINE',
            'status' => 'active',
        ]);

        $this->actingAs($lead)
            ->get(route('business-problems.show', $problem))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('BusinessProblems/Show')
                ->has('problem.dmaic_profiles', 1)
                ->where('problem.dmaic_profiles.0.delivery_project.id', $project->id)
                ->where('problem.dmaic_profiles.0.delivery_project.name', 'Cancellation DMAIC')
            );
    }

    public function test_dmaic_project_hub_includes_problem_and_pillar_context(): void
    {
        $lead = User::factory()->create();
        $lead->assignRole('improvement_lead');

        $pillar = StrategicPillar::create(['name' => 'Cancellation Prevention', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'Init', 'status' => 'active']);
        $problem = BusinessProblem::create([
            'initiative_id' => $initiative->id,
            'title' => 'Late cancellations',
            'status' => 'open',
            'owner_id' => $lead->id,
        ]);
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'Cancellation DMAIC',
            'type' => 'dmaic',
            'status' => 'active',
            'health' => 'green',
            'project_owner_id' => $lead->id,
        ]);
        DmaicProfile::create([
            'delivery_project_id' => $project->id,
            'business_problem_id' => $problem->id,
            'current_phase' => 'DEFINE',
            'status' => 'active',
        ]);

        $this->actingAs($lead)
            ->get(route('delivery-projects.show', $project))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DeliveryProjects/Show')
                ->where('deliveryProject.initiative.strategic_pillar.name', 'Cancellation Prevention')
                ->where('deliveryProject.dmaic_profile.business_problem.title', 'Late cancellations')
            );
    }
}
