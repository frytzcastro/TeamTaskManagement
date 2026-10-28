<?php

namespace Tests\Feature;

use App\Models\DeliveryProject;
use App\Models\System;
use App\Models\StrategicPillar;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationAuthzTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_developer_cannot_manage_pillars(): void
    {
        $user = User::factory()->create();
        $user->assignRole('developer');

        $this->actingAs($user)
            ->post(route('pillars.store'), [
                'name' => 'Should Fail',
                'status' => 'active',
            ])
            ->assertForbidden();
    }

    public function test_product_owner_can_create_strategic_pillar(): void
    {
        $user = User::factory()->create();
        $user->assignRole('product_owner');

        $this->actingAs($user)
            ->post(route('pillars.store'), [
                'name' => 'Cancellation Prevention',
                'description' => 'Test',
                'status' => 'active',
            ])
            ->assertRedirect(route('pillars.index'));

        $this->assertDatabaseHas('strategic_pillars', [
            'name' => 'Cancellation Prevention',
        ]);
    }

    public function test_assignee_can_update_task_status(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('project_manager');

        $developer = User::factory()->create();
        $developer->assignRole('developer');

        $pillar = StrategicPillar::create(['name' => 'Pillar', 'status' => 'active']);
        $initiative = $pillar->initiatives()->create([
            'name' => 'Initiative',
            'status' => 'active',
        ]);
        $deliveryProject = DeliveryProject::create([
            'initiative_id' => $initiative->id,
            'name' => 'Delivery Project',
            'status' => 'active',
            'health' => 'green',
        ]);
        $task = Task::create([
            'delivery_project_id' => $deliveryProject->id,
            'title' => 'API work',
            'assignee_id' => $developer->id,
            'status' => 'ready',
        ]);

        $this->actingAs($developer)
            ->from(route('tasks.show', $task))
            ->put(route('tasks.update', $task), [
                'status' => 'in_progress',
                'estimate' => 2,
                'completion_percent' => 25,
            ])
            ->assertRedirect(route('tasks.show', $task));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
            'completion_percent' => 25,
        ]);
    }

    public function test_api_requires_authentication(): void
    {
        $this->getJson('/api/v1/pillars')->assertUnauthorized();
    }

    public function test_api_lists_pillars_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('executive');

        StrategicPillar::create(['name' => 'Pillar A', 'status' => 'active']);

        $this->actingAs($user)
            ->getJson('/api/v1/pillars')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Pillar A']);
    }

    public function test_developer_cannot_create_system(): void
    {
        $user = User::factory()->create();
        $user->assignRole('developer');

        $this->actingAs($user)
            ->post(route('systems.store'), [
                'name' => 'Should Fail',
                'type' => 'app',
                'status' => 'active',
            ])
            ->assertForbidden();
    }

    public function test_product_owner_can_create_system(): void
    {
        $user = User::factory()->create();
        $user->assignRole('product_owner');

        $this->actingAs($user)
            ->post(route('systems.store'), [
                'name' => 'Reservation App',
                'type' => 'app',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('systems', [
            'name' => 'Reservation App',
        ]);
    }
}
