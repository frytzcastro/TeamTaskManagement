<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessAlignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_product_owner_home_redirects_to_portfolio(): void
    {
        $po = User::factory()->create();
        $po->assignRole('product_owner');

        $this->actingAs($po)
            ->get(route('dashboard'))
            ->assertRedirect(route('portfolio.index'));
    }

    public function test_data_analyst_home_redirects_to_team_dashboard(): void
    {
        $analyst = User::factory()->create();
        $analyst->assignRole('data_analyst');

        $this->actingAs($analyst)
            ->get(route('dashboard'))
            ->assertRedirect(route('dashboards.team'));
    }

    public function test_process_owner_home_redirects_to_dmaic_dashboard(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('process_owner');

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertRedirect(route('dashboards.dmaic'));
    }

    public function test_project_manager_cannot_open_executive_or_developer_dashboards(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('project_manager');

        $this->actingAs($pm)
            ->get(route('dashboards.executive'))
            ->assertForbidden();

        $this->actingAs($pm)
            ->get(route('dashboards.developer'))
            ->assertForbidden();
    }

    public function test_improvement_lead_can_open_schedule(): void
    {
        $lead = User::factory()->create();
        $lead->assignRole('improvement_lead');

        $this->actingAs($lead)
            ->get(route('schedule.index'))
            ->assertOk();
    }

    public function test_developer_home_redirects_to_my_work(): void
    {
        $developer = User::factory()->create();
        $developer->assignRole('developer');

        $this->actingAs($developer)
            ->get(route('dashboard'))
            ->assertRedirect(route('dashboards.developer'));
    }
}
