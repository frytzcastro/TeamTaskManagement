<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $old = Role::query()->where('name', 'six_sigma_leader')->first();
        if (! $old) {
            return;
        }

        $existing = Role::query()->where('name', 'improvement_lead')->first();
        if ($existing) {
            // Move assignments then drop the obsolete slug.
            foreach ($old->users as $user) {
                $user->assignRole($existing);
                $user->removeRole($old);
            }
            $old->delete();
        } else {
            $old->name = 'improvement_lead';
            $old->save();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = Role::query()->where('name', 'improvement_lead')->first();
        if (! $role) {
            return;
        }

        $role->name = 'six_sigma_leader';
        $role->save();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
