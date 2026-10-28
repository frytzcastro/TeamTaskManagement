<?php

namespace Tests\Feature;

use App\Models\DeliveryProject;
use App\Models\DeliveryProjectMilestone;
use App\Models\Epic;
use App\Models\Feature;
use App\Models\Release;
use App\Models\StrategicPillar;
use App\Models\System;
use App\Models\Task;
use App\Models\User;
use App\Services\MilestoneSyncService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AutoMilestoneExpectationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * @return array{0: User, 1: DeliveryProject, 2: System}
     */
    private function pmWithProject(): array
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');
        $pillar = StrategicPillar::create(['name' => 'Pillar', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'Init', 'status' => 'active']);
        $system = System::create([
            'name' => 'Test App',
            'type' => 'app',
            'status' => 'active',
        ]);
        $initiative->systems()->sync([$system->id]);
        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'Expectations Project',
            'type' => 'standard',
            'status' => 'active',
            'health' => 'green',
            'project_owner_id' => $pm->id,
            'target_completion' => now()->addMonths(2)->toDateString(),
        ]);

        return [$pm, $project, $system];
    }

    private function assignFeature(System $system, DeliveryProject $project, Epic $epic, string $title = 'Scoped Feature'): Feature
    {
        return Feature::create([
            'system_id' => $system->id,
            'delivery_project_id' => $project->id,
            'epic_id' => $epic->id,
            'title' => $title,
            'status' => 'in_progress',
            'priority' => 'medium',
            'dod_checklist' => Feature::defaultDodChecklist(),
        ]);
    }

    public function test_creating_epic_with_due_date_and_assigned_feature_creates_milestone(): void
    {
        [$pm, $project, $system] = $this->pmWithProject();

        $this->actingAs($pm)
            ->post(route('epics.store'), [
                'system_id' => $system->id,
                'title' => 'API Checkpoint',
                'status' => 'planned',
                'priority' => 'high',
                'due_date' => now()->addWeeks(3)->toDateString(),
            ])
            ->assertRedirect();

        $epic = Epic::query()->where('title', 'API Checkpoint')->firstOrFail();
        $this->assignFeature($system, $project, $epic);
        app(MilestoneSyncService::class)->syncEpic($epic->fresh());

        $milestone = DeliveryProjectMilestone::query()
            ->where('source_type', MilestoneSyncService::SOURCE_EPIC)
            ->where('source_id', $epic->id)
            ->first();

        $this->assertNotNull($milestone);
        $this->assertSame('API Checkpoint', $milestone->title);
        $this->assertSame($project->id, $milestone->delivery_project_id);
        $this->assertTrue($milestone->due_date->equalTo($epic->due_date));
    }

    public function test_clearing_epic_due_date_removes_auto_milestone(): void
    {
        [$pm, $project, $system] = $this->pmWithProject();
        $epic = Epic::create([
            'system_id' => $system->id,
            'title' => 'Timed Epic',
            'status' => 'planned',
            'priority' => 'medium',
            'due_date' => now()->addWeek()->toDateString(),
        ]);
        $this->assignFeature($system, $project, $epic);
        app(MilestoneSyncService::class)->syncEpic($epic);

        $this->actingAs($pm)
            ->put(route('epics.update', $epic), [
                'system_id' => $system->id,
                'title' => 'Timed Epic',
                'status' => 'planned',
                'priority' => 'medium',
                'due_date' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('delivery_project_milestones', [
            'source_type' => MilestoneSyncService::SOURCE_EPIC,
            'source_id' => $epic->id,
        ]);
    }

    public function test_deleting_epic_removes_auto_milestone(): void
    {
        [$pm, $project, $system] = $this->pmWithProject();
        $epic = Epic::create([
            'system_id' => $system->id,
            'title' => 'Doomed Epic',
            'status' => 'planned',
            'priority' => 'medium',
            'due_date' => now()->addWeek()->toDateString(),
        ]);
        $this->assignFeature($system, $project, $epic);
        app(MilestoneSyncService::class)->syncEpic($epic);

        $this->actingAs($pm)
            ->delete(route('epics.destroy', $epic))
            ->assertRedirect();

        $this->assertDatabaseMissing('delivery_project_milestones', [
            'source_type' => MilestoneSyncService::SOURCE_EPIC,
            'source_id' => $epic->id,
        ]);
    }

    public function test_release_with_target_date_creates_linked_milestone(): void
    {
        [$pm, $project] = $this->pmWithProject();

        $this->actingAs($pm)
            ->post(route('releases.store'), [
                'delivery_project_id' => $project->id,
                'name' => 'Ship v1',
                'version' => '1.0.0',
                'environment' => 'staging',
                'status' => 'planned',
                'target_date' => now()->addDays(10)->toDateString(),
            ])
            ->assertRedirect();

        $release = Release::query()->where('name', 'Ship v1')->firstOrFail();

        $this->assertDatabaseHas('delivery_project_milestones', [
            'delivery_project_id' => $project->id,
            'source_type' => MilestoneSyncService::SOURCE_RELEASE,
            'source_id' => $release->id,
        ]);
    }

    public function test_hub_show_includes_enriched_expectations_and_progress(): void
    {
        [$pm, $project, $system] = $this->pmWithProject();
        $epic = Epic::create([
            'system_id' => $system->id,
            'title' => 'Build Scope',
            'status' => 'in_progress',
            'priority' => 'high',
            'due_date' => now()->addWeeks(2)->toDateString(),
        ]);
        $feature = $this->assignFeature($system, $project, $epic, 'UI');
        Task::create([
            'delivery_project_id' => $project->id,
            'feature_id' => $feature->id,
            'epic_id' => $epic->id,
            'title' => 'Done task',
            'status' => 'done',
            'priority' => 'medium',
        ]);
        Task::create([
            'delivery_project_id' => $project->id,
            'feature_id' => $feature->id,
            'epic_id' => $epic->id,
            'title' => 'Open task',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);

        $this->actingAs($pm)
            ->get(route('delivery-projects.show', $project))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DeliveryProjects/Show')
                ->has('expectations', 1)
                ->where('expectations.0.title', 'Build Scope')
                ->where('expectations.0.source_type', 'epic')
                ->where('expectations.0.progress_pct', 50)
                ->where('expectations.0.health', 'on_track')
                ->has('expectationMovement.next')
                ->where('expectationMovement.target_completion', $project->target_completion->toDateString())
            );
    }

    public function test_manual_milestone_still_creatable(): void
    {
        [$pm, $project] = $this->pmWithProject();

        $this->actingAs($pm)
            ->post(route('delivery-projects.milestones.store', $project), [
                'title' => 'Board review',
                'due_date' => now()->addMonth()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('delivery_project_milestones', [
            'delivery_project_id' => $project->id,
            'title' => 'Board review',
            'source_type' => MilestoneSyncService::SOURCE_MANUAL,
            'source_id' => null,
        ]);
    }

    public function test_feature_and_task_dates_do_not_spawn_milestones(): void
    {
        [$pm, $project, $system] = $this->pmWithProject();

        Feature::create([
            'system_id' => $system->id,
            'delivery_project_id' => $project->id,
            'title' => 'Dated Feature',
            'status' => 'planned',
            'priority' => 'medium',
            'due_date' => now()->addWeek()->toDateString(),
            'dod_checklist' => Feature::defaultDodChecklist(),
        ]);
        Task::create([
            'delivery_project_id' => $project->id,
            'title' => 'Dated Task',
            'status' => 'backlog',
            'priority' => 'medium',
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        app(MilestoneSyncService::class)->reconcileProject($project->fresh(['features', 'releases']));

        $this->assertSame(
            0,
            DeliveryProjectMilestone::query()->where('delivery_project_id', $project->id)->count()
        );
    }

    public function test_cannot_update_auto_milestone_title_via_api(): void
    {
        [$pm, $project, $system] = $this->pmWithProject();
        $epic = Epic::create([
            'system_id' => $system->id,
            'title' => 'Locked Epic',
            'status' => 'planned',
            'priority' => 'medium',
            'due_date' => now()->addWeek()->toDateString(),
        ]);
        $this->assignFeature($system, $project, $epic);
        app(MilestoneSyncService::class)->syncEpic($epic);
        $milestone = DeliveryProjectMilestone::query()
            ->where('source_id', $epic->id)
            ->firstOrFail();

        $this->actingAs($pm)
            ->put(route('delivery-projects.milestones.update', [$project, $milestone]), [
                'title' => 'Hacked title',
                'due_date' => now()->addWeek()->toDateString(),
            ])
            ->assertStatus(422);
    }
}
