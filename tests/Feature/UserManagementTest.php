<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Support\SchoolPermissions as P;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_administrator_can_create_non_student_accounts(): void
    {
        $admin = $this->admin();

        foreach ([Role::ROLE_DIRECTOR, Role::ROLE_PROFESSOR, Role::ROLE_SECRETARY] as $index => $role) {
            $email = "managed-{$index}@example.com";

            $this->actingAs($admin)
                ->post(route('administration.users.store'), [
                    'name' => "Managed {$role}",
                    'email' => $email,
                    'password' => 'Password123!',
                    'password_confirmation' => 'Password123!',
                    'role' => $role,
                    'is_active' => '1',
                ])
                ->assertRedirect(route('administration.users.index'));

            $user = User::where('email', $email)->firstOrFail();

            $this->assertSame($role, $user->user_type);
            $this->assertSame([$role], $user->getRoleNames()->all());
            $this->assertNull($user->student_id);
            $this->assertTrue($user->is_active);
        }
    }

    public function test_student_creation_requires_an_existing_unlinked_student(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('administration.users.store'), [
                'name' => 'Unlinked Student',
                'email' => 'unlinked@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => Role::ROLE_STUDENT,
            ])
            ->assertSessionHasErrors('student_id');

        $this->assertDatabaseMissing('users', ['email' => 'unlinked@example.com']);

        $student = Student::factory()->create();

        $this->actingAs($admin)
            ->post(route('administration.users.store'), [
                'name' => 'Linked Student',
                'email' => 'linked@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => Role::ROLE_STUDENT,
                'student_id' => $student->id,
            ])
            ->assertRedirect(route('administration.users.index'));

        $user = User::where('email', 'linked@example.com')->firstOrFail();

        $this->assertSame($student->id, $user->student_id);
        $this->assertSame(Role::ROLE_STUDENT, $user->user_type);
        $this->assertSame([Role::ROLE_STUDENT], $user->getRoleNames()->all());
    }

    public function test_director_can_only_be_changed_to_student_with_a_student_link(): void
    {
        $admin = $this->admin();
        $director = User::factory()->create([
            'user_type' => Role::ROLE_DIRECTOR,
            'is_active' => true,
        ]);
        $director->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($admin)
            ->put(route('administration.users.update', $director), [
                'name' => $director->name,
                'email' => $director->email,
                'role' => Role::ROLE_STUDENT,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('student_id');

        $director->refresh();
        $this->assertSame(Role::ROLE_DIRECTOR, $director->user_type);
        $this->assertSame([Role::ROLE_DIRECTOR], $director->getRoleNames()->all());

        $student = Student::factory()->create();

        $this->actingAs($admin)
            ->put(route('administration.users.update', $director), [
                'name' => $director->name,
                'email' => $director->email,
                'role' => Role::ROLE_STUDENT,
                'student_id' => $student->id,
                'is_active' => '1',
            ])
            ->assertRedirect(route('administration.users.index'));

        $director->refresh();
        $this->assertSame($student->id, $director->student_id);
        $this->assertSame(Role::ROLE_STUDENT, $director->user_type);
        $this->assertSame([Role::ROLE_STUDENT], $director->getRoleNames()->all());
        $this->assertFalse($director->hasRole(Role::ROLE_DIRECTOR));
    }

    public function test_operational_administrator_can_deactivate_reactivate_and_reset_another_user_password(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create([
            'user_type' => Role::ROLE_DIRECTOR,
            'is_active' => true,
        ]);
        $user->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($admin)
            ->put(route('administration.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'Replacement123!',
                'password_confirmation' => 'Replacement123!',
                'role' => Role::ROLE_DIRECTOR,
                'is_active' => '0',
            ])
            ->assertRedirect(route('administration.users.index'));

        $user->refresh();
        $this->assertFalse($user->is_active);
        $this->assertTrue(Hash::check('Replacement123!', $user->password));

        $this->actingAs($admin)
            ->put(route('administration.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => Role::ROLE_DIRECTOR,
                'is_active' => '1',
            ])
            ->assertRedirect(route('administration.users.index'));

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_only_operational_administrator_can_manage_users(): void
    {
        $managedUser = User::factory()->create();

        foreach ([Role::ROLE_DIRECTOR, Role::ROLE_SECRETARY, Role::ROLE_PROFESSOR, Role::ROLE_STUDENT] as $role) {
            $user = User::factory()->create([
                'user_type' => $role,
                'student_id' => $role === Role::ROLE_STUDENT ? Student::factory()->create()->id : null,
            ]);
            $user->assignRole($role);

            $this->actingAs($user)
                ->get(route('administration.users.index'))
                ->assertForbidden();

            $this->actingAs($user)
                ->post(route('administration.users.store'), [])
                ->assertForbidden();

            $this->actingAs($user)
                ->get(route('administration.users.edit', $managedUser))
                ->assertForbidden();

            $this->actingAs($user)
                ->put(route('administration.users.update', $managedUser), ['is_active' => '0'])
                ->assertForbidden();

            $this->actingAs($user)
                ->delete(route('administration.users.destroy', $managedUser))
                ->assertForbidden();

            $this->actingAs($user)
                ->get(route('settings.update-users'))
                ->assertForbidden();
        }

        $this->actingAs($this->admin())
            ->get(route('administration.users.index'))
            ->assertOk();
    }

    public function test_user_management_permissions_do_not_replace_the_administrator_role(): void
    {
        $director = User::factory()->create(['user_type' => Role::ROLE_DIRECTOR]);
        $director->assignRole(Role::ROLE_DIRECTOR);
        $director->givePermissionTo([
            P::USERS_VIEW,
            P::USERS_CREATE,
            P::USERS_UPDATE,
            P::USERS_DELETE,
        ]);

        $this->actingAs($director)->get(route('administration.users.index'))->assertForbidden();
        $this->actingAs($director)->post(route('administration.users.store'), [])->assertForbidden();
        $this->actingAs($director)->get(route('settings.update-users'))->assertForbidden();
    }

    public function test_user_email_must_remain_unique_when_created_or_updated(): void
    {
        $admin = $this->admin();
        $existing = User::factory()->create();
        $other = User::factory()->create(['user_type' => Role::ROLE_DIRECTOR]);
        $other->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($admin)->post(route('administration.users.store'), [
            'name' => 'Duplicate Email',
            'email' => $existing->email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => Role::ROLE_DIRECTOR,
        ])->assertSessionHasErrors('email');

        $this->actingAs($admin)->put(route('administration.users.update', $other), [
            'name' => $other->name,
            'email' => $existing->email,
            'role' => Role::ROLE_DIRECTOR,
            'is_active' => '1',
        ])->assertSessionHasErrors('email');

        $this->assertSame($other->email, $other->fresh()->email);
    }

    public function test_role_update_removes_every_stale_spatie_role(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['user_type' => Role::ROLE_DIRECTOR]);
        $user->assignRole([Role::ROLE_DIRECTOR, Role::ROLE_PROFESSOR]);

        $this->actingAs($admin)->put(route('administration.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => Role::ROLE_SECRETARY,
            'is_active' => '1',
        ])->assertRedirect(route('administration.users.index'));

        $user->refresh();

        $this->assertSame([Role::ROLE_SECRETARY], $user->getRoleNames()->all());
        $this->assertSame(Role::ROLE_SECRETARY, $user->user_type);
    }

    public function test_student_link_cannot_be_shared_by_two_student_users(): void
    {
        $admin = $this->admin();
        $student = Student::factory()->create();
        $linkedUser = User::factory()->create([
            'student_id' => $student->id,
            'user_type' => Role::ROLE_STUDENT,
        ]);
        $linkedUser->assignRole(Role::ROLE_STUDENT);

        $this->actingAs($admin)->post(route('administration.users.store'), [
            'name' => 'Duplicate Student Link',
            'email' => 'duplicate-student-link@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => Role::ROLE_STUDENT,
            'student_id' => $student->id,
        ])->assertSessionHasErrors('student_id');

        $this->assertDatabaseMissing('users', ['email' => 'duplicate-student-link@example.com']);
    }

    public function test_operational_administrator_cannot_lock_themselves_out(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('administration.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => Role::ROLE_DIRECTOR,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('role');

        $admin->refresh();
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMINISTRATOR));
        $this->assertSame(Role::ROLE_ADMINISTRATOR, $admin->user_type);

        $this->actingAs($admin)
            ->put(route('administration.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => Role::ROLE_ADMINISTRATOR,
                'is_active' => '0',
            ])
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($admin->fresh()->is_active);

        $this->actingAs($admin)
            ->delete(route('administration.users.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $this->actingAs($admin)
            ->get(route('administration.users.index'))
            ->assertOk()
            ->assertDontSee('action="'.route('administration.users.destroy', $admin).'"', false);
    }

    public function test_user_administration_renders_account_status_and_permitted_actions(): void
    {
        $admin = $this->admin();
        $activeUser = User::factory()->create([
            'name' => 'Active Managed User',
            'user_type' => Role::ROLE_DIRECTOR,
            'is_active' => true,
        ]);
        $activeUser->assignRole(Role::ROLE_DIRECTOR);
        $inactiveUser = User::factory()->create([
            'name' => 'Inactive Managed User',
            'user_type' => Role::ROLE_PROFESSOR,
            'is_active' => false,
        ]);
        $inactiveUser->assignRole(Role::ROLE_PROFESSOR);

        $this->actingAs($admin)
            ->get(route('administration.users.index'))
            ->assertOk()
            ->assertSee('Active Managed User')
            ->assertSee('Inactive Managed User')
            ->assertSee('Active')
            ->assertSee('Inactive')
            ->assertSee(route('administration.users.edit', $activeUser), false)
            ->assertSee(route('administration.users.destroy', $inactiveUser), false);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@gmail.com')->firstOrFail();
    }
}
