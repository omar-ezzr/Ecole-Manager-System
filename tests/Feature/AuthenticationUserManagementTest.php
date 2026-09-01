<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_seeder_does_not_change_an_existing_administrator(): void
    {
        $admin = $this->admin();
        $originalId = $admin->id;
        $originalPassword = $admin->getRawOriginal('password');
        $originalRoles = $admin->getRoleNames()->sort()->values()->all();

        $this->seed(AdminUserSeeder::class);

        $admin->refresh();

        $this->assertSame($originalId, $admin->id);
        $this->assertSame($originalPassword, $admin->getRawOriginal('password'));
        $this->assertSame($originalRoles, $admin->getRoleNames()->sort()->values()->all());
    }

    public function test_administrator_created_active_user_has_usable_credentials(): void
    {
        $this->actingAs($this->admin())
            ->post(route('administration.users.store'), [
                'name' => 'Managed Director',
                'email' => 'managed-director@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => Role::ROLE_DIRECTOR,
                'is_active' => '1',
            ])
            ->assertRedirect(route('administration.users.index'));

        $this->assertTrue(Auth::validate([
            'email' => 'managed-director@example.com',
            'password' => 'Password123!',
        ]));
    }

    public function test_user_update_without_password_preserves_existing_credentials(): void
    {
        $user = $this->director();
        $password = $user->getRawOriginal('password');

        $this->actingAs($this->admin())
            ->put(route('administration.users.update', $user), [
                'name' => 'Updated Director',
                'email' => $user->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => Role::ROLE_DIRECTOR,
                'is_active' => '1',
            ])
            ->assertRedirect(route('administration.users.index'));

        $user->refresh();

        $this->assertSame($password, $user->getRawOriginal('password'));
        $this->assertTrue(Auth::validate([
            'email' => $user->email,
            'password' => 'password',
        ]));
    }

    public function test_user_password_update_produces_usable_credentials(): void
    {
        $user = $this->director();

        $this->actingAs($this->admin())
            ->put(route('administration.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'Replacement123!',
                'password_confirmation' => 'Replacement123!',
                'role' => Role::ROLE_DIRECTOR,
                'is_active' => '1',
            ])
            ->assertRedirect(route('administration.users.index'));

        $this->assertTrue(Auth::validate([
            'email' => $user->email,
            'password' => 'Replacement123!',
        ]));
        $this->assertFalse(Auth::validate([
            'email' => $user->email,
            'password' => 'password',
        ]));
    }

    private function admin(): User
    {
        return User::where('email', 'admin@gmail.com')->firstOrFail();
    }

    private function director(): User
    {
        $user = User::factory()->create([
            'user_type' => Role::ROLE_DIRECTOR,
            'is_active' => true,
        ]);
        $user->assignRole(Role::ROLE_DIRECTOR);

        return $user;
    }
}
