<?php

namespace Tests\Feature;

use App\Models\Role as SchoolRole;
use App\Models\User;
use App\Support\SchoolPermissions as P;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleNameMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_service_secretaire_role_merges_into_service_secretariat(): void
    {
        $newRole = Role::findByName(SchoolRole::ROLE_SECRETARY, 'web');
        $oldRole = Role::create(['name' => 'Service Secrétaire', 'guard_name' => 'web']);
        $permission = Permission::findByName(P::STUDENTS_CREATE, 'web');
        $oldRole->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($oldRole);
        $user->assignRole($newRole);

        $migration = require database_path('migrations/2026_07_23_020000_migrate_service_secretaire_role.php');
        $migration->up();

        $user->refresh();

        $this->assertTrue($user->hasRole(SchoolRole::ROLE_SECRETARY));
        $this->assertTrue($user->can(P::STUDENTS_CREATE));
        $this->assertDatabaseMissing('roles', ['name' => 'Service Secrétaire']);
        $this->assertSame(1, DB::table(config('permission.table_names.model_has_roles'))
            ->where('role_id', $newRole->id)
            ->where('model_id', $user->id)
            ->where('model_type', User::class)
            ->count());
    }
}
