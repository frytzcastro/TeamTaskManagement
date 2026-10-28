<?php

namespace Tests\Feature;

use App\Models\BusinessProblem;
use App\Models\DeliveryProject;
use App\Models\Dmaic\DmaicControlMetric;
use App\Models\Dmaic\DmaicGate;
use App\Models\Dmaic\DmaicProfile;
use App\Models\Epic;
use App\Models\Feature;
use App\Models\Kpi;
use App\Models\StrategicPillar;
use App\Models\User;
use App\Services\DmaicWorkflowService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DmaicAndFoundationExtensionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_delivery_project_persists_type_and_kpi_metric_layer(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');

        $pillar = StrategicPillar::create(['name' => 'Pillar', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'Init', 'status' => 'active']);

        $this->actingAs($pm)
            ->post(route('delivery-projects.store'), [
                'initiative_id' => $initiative->id,
                'name' => 'Standard Build',
                'type' => 'standard',
                'status' => 'active',
                'health' => 'green',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('delivery_projects', [
            'name' => 'Standard Build',
            'type' => 'standard',
        ]);

        $po = User::factory()->create();
        $po->assignRole('product_owner');

        $this->actingAs($po)
            ->post(route('kpis.store'), [
                'name' => 'Cycle Time',
                'metric_layer' => 'process',
                'direction' => 'lower_is_better',
            ])
            ->assertRedirect();

        $kpi = \App\Models\Kpi::where('name', 'Cycle Time')->firstOrFail();
        $this->assertSame('process', $kpi->metric_layer);
        $this->assertNotEmpty($kpi->code);
        $this->assertStringStartsWith('KPI-', $kpi->code);
    }

    public function test_project_manager_can_crud_epic_and_feature(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');

        $pillar = StrategicPillar::create(['name' => 'Pillar', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'Init', 'status' => 'active']);
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'DP',
            'type' => 'standard',
            'status' => 'active',
            'health' => 'green',
        ]);

        $system = \App\Models\System::create(['name' => 'App', 'type' => 'app', 'status' => 'active']);

        $this->actingAs($pm)
            ->post(route('epics.store'), [
                'system_id' => $system->id,
                'title' => 'Epic One',
                'status' => 'planned',
                'priority' => 'high',
            ])
            ->assertRedirect();

        $epic = Epic::firstOrFail();

        $this->actingAs($pm)
            ->post(route('features.store'), [
                'system_id' => $system->id,
                'delivery_project_id' => $project->id,
                'epic_id' => $epic->id,
                'title' => 'Feature One',
                'status' => 'planned',
                'priority' => 'medium',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('features', [
            'title' => 'Feature One',
            'epic_id' => $epic->id,
        ]);
    }

    public function test_developer_cannot_manage_epics(): void
    {
        $dev = User::factory()->create();
        $dev->assignRole('developer');

        $pillar = StrategicPillar::create(['name' => 'Pillar', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'Init', 'status' => 'active']);
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'DP',
            'status' => 'active',
            'health' => 'green',
        ]);

        $system = \App\Models\System::create(['name' => 'App', 'type' => 'app', 'status' => 'active']);

        $this->actingAs($dev)
            ->post(route('epics.store'), [
                'system_id' => $system->id,
                'title' => 'Nope',
                'status' => 'planned',
            ])
            ->assertForbidden();
    }

    public function test_dmaic_bootstrap_only_for_dmaic_type_and_define_gate_blocks_advance(): void
    {
        $ssl = User::factory()->create();
        $ssl->assignRole('improvement_lead');

        $pillar = StrategicPillar::create(['name' => 'Pillar', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'Init', 'status' => 'active']);
        $standard = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'Standard',
            'type' => 'standard',
            'status' => 'active',
            'health' => 'green',
        ]);

        $this->actingAs($ssl)
            ->post(route('delivery-projects.dmaic.bootstrap', $standard))
            ->assertStatus(422);

        $dmaicProject = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'DMAIC DP',
            'type' => 'dmaic',
            'status' => 'active',
            'health' => 'green',
            'project_owner_id' => $ssl->id,
        ]);

        $problem = BusinessProblem::create([
            'initiative_id' => $initiative->id,
            'title' => 'Problem',
            'status' => 'open',
            'owner_id' => $ssl->id,
        ]);

        $this->actingAs($ssl)
            ->post(route('delivery-projects.dmaic.bootstrap', $dmaicProject), [
                'business_problem_id' => $problem->id,
            ])
            ->assertRedirect();

        $profile = DmaicProfile::where('delivery_project_id', $dmaicProject->id)->firstOrFail();
        $this->assertSame('DEFINE', $profile->current_phase);

        $gate = DmaicGate::whereHas('phase', fn ($q) => $q->where('dmaic_profile_id', $profile->id)->where('phase', 'DEFINE'))->firstOrFail();

        $this->actingAs($ssl)
            ->from(route('dmaic.show', $profile))
            ->post(route('dmaic.gates.approve', $gate))
            ->assertRedirect()
            ->assertSessionHasErrors('gate');

        $this->assertSame('DEFINE', $profile->fresh()->current_phase);

        $define = $profile->phases()->where('phase', 'DEFINE')->firstOrFail();
        $define->deliverables()->update(['completed' => true]);

        $this->actingAs($ssl)
            ->post(route('dmaic.gates.approve', $gate->fresh()), ['comments' => 'OK'])
            ->assertRedirect();

        $this->assertSame('MEASURE', $profile->fresh()->current_phase);
    }

    public function test_measure_and_analyze_gates_enforce_phase_criteria(): void
    {
        $ssl = User::factory()->create();
        $ssl->assignRole('improvement_lead');

        $profile = $this->bootstrappedProfile($ssl);
        $workflow = app(DmaicWorkflowService::class);

        $define = $profile->phases()->where('phase', 'DEFINE')->firstOrFail();
        $define->deliverables()->update(['completed' => true]);
        $workflow->approveGate($define->gates()->firstOrFail(), $ssl);

        $profile->refresh();
        $this->assertSame('MEASURE', $profile->current_phase);

        $measureGate = $profile->phases()->where('phase', 'MEASURE')->firstOrFail()->gates()->firstOrFail();
        $this->actingAs($ssl)
            ->from(route('dmaic.show', $profile))
            ->post(route('dmaic.gates.approve', $measureGate))
            ->assertSessionHasErrors('gate');

        $this->actingAs($ssl)
            ->post(route('dmaic.measurement-plans.store', $profile), [
                'metric_name' => 'Cancel rate',
                'baseline_value' => 10,
                'data_quality_status' => 'good',
            ])
            ->assertRedirect();

        $measurePhase = $profile->phases()->where('phase', 'MEASURE')->firstOrFail();
        $measurePhase->deliverables()->update(['completed' => true]);

        $this->actingAs($ssl)
            ->post(route('dmaic.gates.approve', $measureGate->fresh()))
            ->assertRedirect();

        $this->assertSame('ANALYZE', $profile->fresh()->current_phase);

        $analyzeGate = $profile->phases()->where('phase', 'ANALYZE')->firstOrFail()->gates()->firstOrFail();
        $this->actingAs($ssl)
            ->from(route('dmaic.show', $profile))
            ->post(route('dmaic.gates.approve', $analyzeGate))
            ->assertSessionHasErrors('gate');

        $this->actingAs($ssl)
            ->post(route('dmaic.root-causes.store', $profile), [
                'title' => 'Late scoring',
                'status' => 'validated',
                'evidence_title' => 'Timing study',
                'evidence_body' => 'Evidence body',
            ])
            ->assertRedirect();

        $analyzePhase = $profile->phases()->where('phase', 'ANALYZE')->firstOrFail();
        $analyzePhase->deliverables()->update(['completed' => true]);

        $this->actingAs($ssl)
            ->post(route('dmaic.gates.approve', $analyzeGate->fresh()))
            ->assertRedirect();

        $this->assertSame('IMPROVE', $profile->fresh()->current_phase);
    }

    public function test_improvement_can_create_feature_and_bug_can_link_dmaic(): void
    {
        $ssl = User::factory()->create();
        $ssl->assignRole('improvement_lead');
        $dev = User::factory()->create();
        $dev->assignRole('developer');

        $profile = $this->bootstrappedProfile($ssl);
        $root = $profile->rootCauses()->create([
            'title' => 'Cause',
            'status' => 'validated',
        ]);

        $this->actingAs($ssl)
            ->post(route('dmaic.improvements.store', $profile), [
                'title' => 'Earlier risk check',
                'dmaic_root_cause_id' => $root->id,
                'create_feature' => true,
                'status' => 'planned',
                'before_measurement' => 10,
                'expected_measurement' => 8.5,
            ])
            ->assertRedirect();

        $improvement = $profile->improvements()->firstOrFail();
        $this->assertNotNull($improvement->feature_id);
        $this->assertDatabaseHas('features', [
            'id' => $improvement->feature_id,
            'title' => 'Earlier risk check',
        ]);

        $this->actingAs($dev)
            ->post(route('bugs.store'), [
                'title' => 'Linked bug',
                'severity' => 'high',
                'environment' => 'staging',
                'status' => 'reported',
                'delivery_project_id' => $profile->delivery_project_id,
                'dmaic_profile_id' => $profile->id,
                'dmaic_root_cause_id' => $root->id,
                'dmaic_improvement_id' => $improvement->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bugs', [
            'title' => 'Linked bug',
            'dmaic_profile_id' => $profile->id,
            'dmaic_root_cause_id' => $root->id,
            'dmaic_improvement_id' => $improvement->id,
        ]);
    }

    public function test_control_breach_creates_alert_and_dmaic_dashboard_loads(): void
    {
        $ssl = User::factory()->create();
        $ssl->assignRole('improvement_lead');

        $profile = $this->bootstrappedProfile($ssl);

        $this->actingAs($ssl)
            ->post(route('dmaic.control-plans.store', $profile), [
                'title' => 'Sustain plan',
                'metric_name' => 'Cancel rate',
                'baseline' => 10,
                'target' => 8.5,
                'current_value' => 12,
                'ucl' => 9,
                'lcl' => 7,
            ])
            ->assertRedirect();

        $metric = DmaicControlMetric::firstOrFail();
        $this->assertDatabaseHas('dmaic_control_alerts', [
            'dmaic_control_metric_id' => $metric->id,
            'status' => 'open',
        ]);

        $this->actingAs($ssl)
            ->get(route('dashboards.dmaic'))
            ->assertOk();
    }

    public function test_gate_reject_keeps_phase(): void
    {
        $ssl = User::factory()->create();
        $ssl->assignRole('improvement_lead');
        $profile = $this->bootstrappedProfile($ssl);
        $define = $profile->phases()->where('phase', 'DEFINE')->firstOrFail();
        $define->deliverables()->update(['completed' => true]);
        $gate = $define->gates()->firstOrFail();

        $this->actingAs($ssl)
            ->post(route('dmaic.gates.reject', $gate), [
                'rejection_reason' => 'Incomplete scope',
            ])
            ->assertRedirect();

        $this->assertSame('rejected', $gate->fresh()->status);
        $this->assertSame('DEFINE', $profile->fresh()->current_phase);
    }

    private function bootstrappedProfile(User $leader): DmaicProfile
    {
        $pillar = StrategicPillar::create(['name' => 'Pillar '.uniqid(), 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'Init', 'status' => 'active']);
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'DMAIC '.uniqid(),
            'type' => 'dmaic',
            'status' => 'active',
            'health' => 'green',
            'project_owner_id' => $leader->id,
        ]);

        return app(DmaicWorkflowService::class)->bootstrap($project, null, $leader);
    }
}
