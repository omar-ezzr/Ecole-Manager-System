<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class SyncUserRolesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_synchronizes_supported_roles_and_safely_skips_unknown_user_types(): void
    {
        $user = User::factory()->create(['user_type' => Role::ROLE_DIRECTOR]);
        $user->assignRole(Role::ROLE_STUDENT);

        $invalidUser = User::factory()->create(['user_type' => 'Unknown Role']);
        $invalidUser->assignRole(Role::ROLE_STUDENT);

        $password = $invalidUser->password;
        $studentId = $invalidUser->student_id;

        $this->artisan('users:sync-roles')
            ->expectsOutputToContain(sprintf('UPDATED #%d %s', $user->id, $user->email))
            ->expectsOutputToContain(sprintf('SKIPPED #%d %s', $invalidUser->id, $invalidUser->email))
            ->assertSuccessful();

        $user = $user->fresh();
        $invalidUser = $invalidUser->fresh();

        $this->assertSame([Role::ROLE_DIRECTOR], $user->getRoleNames()->all());
        $this->assertFalse($user->hasRole(Role::ROLE_STUDENT));
        $this->assertSame(Role::ROLE_DIRECTOR, $user->user_type);

        $this->assertSame([Role::ROLE_STUDENT], $invalidUser->getRoleNames()->all());
        $this->assertSame('Unknown Role', $invalidUser->user_type);
        $this->assertSame($password, $invalidUser->password);
        $this->assertSame($studentId, $invalidUser->student_id);
        $this->assertFalse(SpatieRole::where('name', 'Unknown Role')->exists());
    }
}
