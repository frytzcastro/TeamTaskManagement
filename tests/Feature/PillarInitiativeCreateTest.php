<?php

namespace Tests\Feature;

use App\Models\Initiative;
use App\Models\StrategicPillar;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PillarInitiativeCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pillar_show_exposes_initiative_manage_flag(): void
    {
        $po = User::factory()->create();
        $po->assignRole('product_owner');
        $pillar = StrategicPillar::create(['name' => 'Growth', 'status' => 'active']);

        $this->actingAs($po)
            ->get(route('pillars.show', $pillar))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pillars/Show')
                ->where('canManageInitiatives', true)
                ->where('pillar.id', $pillar->id)
            );
    }

    public function test_initiative_create_from_pillar_prefills_pillar_and_return(): void
    {
        $po = User::factory()->create();
        $po->assignRole('product_owner');
        $pillar = StrategicPillar::create(['name' => 'Growth', 'status' => 'active']);

        $this->actingAs($po)
            ->get(route('initiatives.create', [
                'strategic_pillar_id' => $pillar->id,
                'return_to_pillar' => 1,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Initiatives/Form')
                ->where('defaultStrategicPillarId', $pillar->id)
                ->where('returnPillar.id', $pillar->id)
                ->where('returnPillar.name', 'Growth')
            );
    }

    public function test_initiative_store_from_pillar_returns_to_pillar_show(): void
    {
        $po = User::factory()->create();
        $po->assignRole('product_owner');
        $pillar = StrategicPillar::create(['name' => 'Growth', 'status' => 'active']);

        $this->actingAs($po)
            ->post(route('initiatives.store'), [
                'strategic_pillar_id' => $pillar->id,
                'name' => 'Retention bet',
                'status' => 'planned',
                'priority' => 'medium',
                'return_pillar_id' => $pillar->id,
            ])
            ->assertRedirect(route('pillars.show', $pillar));

        $this->assertDatabaseHas('initiatives', [
            'name' => 'Retention bet',
            'strategic_pillar_id' => $pillar->id,
        ]);
        $this->assertSame(1, Initiative::query()->where('strategic_pillar_id', $pillar->id)->count());
    }
}
