<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'pillars.view',
            'pillars.manage',
            'initiatives.view',
            'initiatives.manage',
            'business_problems.view',
            'business_problems.manage',
            'delivery_projects.view',
            'delivery_projects.manage',
            'systems.view',
            'systems.manage',
            'epics.view',
            'epics.manage',
            'features.view',
            'features.manage',
            'tasks.view',
            'tasks.manage',
            'tasks.update',
            'bugs.view',
            'bugs.manage',
            'bugs.update',
            'support.view',
            'support.manage',
            'support.update',
            'support.convert',
            'releases.view',
            'releases.manage',
            'sprints.view',
            'sprints.manage',
            'kpis.view',
            'kpis.manage',
            'dmaic.view',
            'dmaic.manage',
            'dmaic.gate_approve',
            'users.manage',
            'dashboards.executive',
            'dashboards.developer',
            'dashboards.team',
            'dashboards.dmaic',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $rolePermissions = [
            'admin' => $permissions,
            'executive' => [
                'pillars.view',
                'initiatives.view',
                'business_problems.view',
                'delivery_projects.view',
                'systems.view',
                'epics.view',
                'features.view',
                'tasks.view',
                'bugs.view',
                'support.view',
                'releases.view',
                'sprints.view',
                'kpis.view',
                'dmaic.view',
                'dashboards.executive',
                'dashboards.team',
                'dashboards.dmaic',
            ],
            'product_owner' => [
                'pillars.view',
                'pillars.manage',
                'initiatives.view',
                'initiatives.manage',
                'business_problems.view',
                'business_problems.manage',
                'delivery_projects.view',
                'systems.view',
                'systems.manage',
                'epics.view',
                'features.view',
                'features.manage',
                'tasks.view',
                'bugs.view',
                'bugs.update',
                'support.view',
                'releases.view',
                'sprints.view',
                'kpis.view',
                'kpis.manage',
                'dmaic.view',
                'dashboards.executive',
                'dashboards.team',
                'dashboards.dmaic',
            ],
            'project_manager' => [
                'pillars.view',
                'initiatives.view',
                'business_problems.view',
                'business_problems.manage',
                'delivery_projects.view',
                'delivery_projects.manage',
                'systems.view',
                'epics.view',
                'epics.manage',
                'features.view',
                'features.manage',
                'tasks.view',
                'tasks.manage',
                'tasks.update',
                'bugs.view',
                'bugs.manage',
                'bugs.update',
                'support.view',
                'support.manage',
                'support.update',
                'support.convert',
                'releases.view',
                'releases.manage',
                'sprints.view',
                'sprints.manage',
                'kpis.view',
                'dmaic.view',
                'dmaic.manage',
                'dmaic.gate_approve',
                'dashboards.team',
                'dashboards.dmaic',
            ],
            'improvement_lead' => [
                'pillars.view',
                'initiatives.view',
                'business_problems.view',
                'business_problems.manage',
                'delivery_projects.view',
                'systems.view',
                'epics.view',
                'features.view',
                'tasks.view',
                'bugs.view',
                'support.view',
                'releases.view',
                'sprints.view',
                'kpis.view',
                'kpis.manage',
                'dmaic.view',
                'dmaic.manage',
                'dmaic.gate_approve',
                'dashboards.team',
                'dashboards.dmaic',
            ],
            'process_owner' => [
                'pillars.view',
                'initiatives.view',
                'business_problems.view',
                'delivery_projects.view',
                'systems.view',
                'epics.view',
                'features.view',
                'tasks.view',
                'bugs.view',
                'kpis.view',
                'dmaic.view',
                'dmaic.manage',
                'dashboards.dmaic',
            ],
            'data_analyst' => [
                'pillars.view',
                'initiatives.view',
                'delivery_projects.view',
                'systems.view',
                'tasks.view',
                'bugs.view',
                'kpis.view',
                'kpis.manage',
                'dmaic.view',
                'dashboards.team',
                'dashboards.dmaic',
            ],
            'developer' => [
                'pillars.view',
                'initiatives.view',
                'delivery_projects.view',
                'systems.view',
                'epics.view',
                'features.view',
                'tasks.view',
                'tasks.update',
                'bugs.view',
                'bugs.update',
                'support.view',
                'releases.view',
                'sprints.view',
                'kpis.view',
                'dmaic.view',
                'dashboards.developer',
            ],
            'qa' => [
                'pillars.view',
                'initiatives.view',
                'delivery_projects.view',
                'systems.view',
                'epics.view',
                'features.view',
                'tasks.view',
                'tasks.update',
                'bugs.view',
                'bugs.update',
                'releases.view',
                'releases.manage',
                'sprints.view',
                'kpis.view',
                'dmaic.view',
                'dashboards.developer',
            ],
            'support' => [
                'delivery_projects.view',
                'systems.view',
                'tasks.view',
                'bugs.view',
                'bugs.update',
                'support.view',
                'support.manage',
                'support.update',
                'support.convert',
                'kpis.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::findOrCreate($roleName);
            $role->syncPermissions($perms);
        }

        // Drop obsolete slug if a prior seed left it behind (migration also renames).
        $legacy = Role::query()->where('name', 'six_sigma_leader')->first();
        if ($legacy) {
            $canonical = Role::findByName('improvement_lead');
            foreach ($legacy->users as $user) {
                $user->assignRole($canonical);
                $user->removeRole($legacy);
            }
            $legacy->delete();
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }

        foreach ([
            ['priority' => 'critical', 'response_minutes' => 15, 'resolution_minutes' => 240],
            ['priority' => 'high', 'response_minutes' => 60, 'resolution_minutes' => 480],
            ['priority' => 'medium', 'response_minutes' => 240, 'resolution_minutes' => 2880],
            ['priority' => 'low', 'response_minutes' => 1440, 'resolution_minutes' => 7200],
        ] as $sla) {
            \App\Models\SupportSla::query()->updateOrCreate(['priority' => $sla['priority']], $sla);
        }
    }
}
