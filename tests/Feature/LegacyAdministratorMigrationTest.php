<?php

namespace Tests\Feature;

use App\Models\Role as SchoolRole;
use App\Models\User;
use App\Support\SchoolPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LegacyAdministratorMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_administrator_is_upgraded_without_changing_credentials(): void
    {
        $newRole = Role::findByName(SchoolRole::ROLE_ADMINISTRATOR, 'web');
        $oldRole = Role::create(['name' => 'Administrator', 'guard_name' => 'web']);
        $user = User::factory()->create(['user_type' => 'user', 'is_active' => true]);
        $password = $user->getRawOriginal('password');
        $user->assignRole($oldRole);

        $this->assertTrue(Auth::validate([
            'email' => $user->email,
            'password' => 'password',
        ]));
        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
        Auth::logout();

        $migration = require database_path('migrations/2026_08_31_000000_migrate_administrator_role.php');
        $migration->up();

        $user->refresh();

        $this->assertSame($password, $user->getRawOriginal('password'));
        $this->assertSame(SchoolRole::ROLE_ADMINISTRATOR, $user->user_type);
        $this->assertTrue($user->hasRole(SchoolRole::ROLE_ADMINISTRATOR));
        $this->assertTrue($user->can(SchoolPermissions::DASHBOARD_GLOBAL));
        $this->assertTrue(Auth::validate([
            'email' => $user->email,
            'password' => 'password',
        ]));
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->assertDatabaseMissing('roles', ['name' => 'Administrator']);
        $this->assertSame(1, DB::table(config('permission.table_names.model_has_roles'))
            ->where('role_id', $newRole->id)
            ->where('model_id', $user->id)
            ->where('model_type', User::class)
            ->count());
    }
}
