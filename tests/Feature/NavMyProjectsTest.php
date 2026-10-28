<?php

namespace Tests\Feature;

use App\Models\DeliveryProject;
use App\Models\StrategicPillar;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NavMyProjectsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeProject(string $name, ?User $owner = null, ?User $member = null): DeliveryProject
    {
        $pillar = StrategicPillar::create(['name' => 'Pillar '.$name, 'status' => 'active']);
        $initiative = $pillar->initiatives()->create(['name' => 'Init '.$name, 'status' => 'active']);

        $project = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => $name,
            'type' => 'standard',
            'status' => 'active',
            'health' => 'green',
            'project_owner_id' => $owner?->id,
        ]);

        if ($member) {
            $project->members()->attach($member->id);
        }

        return $project;
    }

    public function test_shared_nav_includes_owned_and_member_projects(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');

        $owned = $this->makeProject('Owned Hub', $pm);
        $asMember = $this->makeProject('Member Hub', User::factory()->create(), $pm);
        $this->makeProject('Other Hub', User::factory()->create());

        $this->actingAs($pm)
            ->get(route('delivery-projects.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('nav.my_projects', 2)
                ->where('nav.my_projects', fn ($projects) => collect($projects)->pluck('id')->sort()->values()->all()
                    === collect([$owned->id, $asMember->id])->sort()->values()->all())
            );
    }

    public function test_shared_nav_empty_without_delivery_projects_view(): void
    {
        $user = User::factory()->create();
        // No roles — no delivery_projects.view
        $this->makeProject('Hidden', $user);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('nav.my_projects', [])
            );
    }

    public function test_delivery_project_hub_includes_board_columns(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');
        $project = $this->makeProject('Board Hub', $pm);

        $this->actingAs($pm)
            ->get(route('delivery-projects.show', $project))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DeliveryProjects/Show')
                ->has('board')
                ->where('board.0.id', 'backlog')
            );
    }
}
