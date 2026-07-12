<?php

namespace Tests\Feature;
 
use App\Models\Role;
use App\Models\User;
use App\Support\SchoolPermissions as P;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
 
class AuthTest extends TestCase
{
    use RefreshDatabase;
 
    public function test_api_registration_ignores_requested_admin_role_and_assigns_student()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Valid name',
            'email' => 'valid@email.com',
            'password' => 'ValidPassword',
            'password_confirmation' => 'ValidPassword',
            'role_id' => Role::ROLE_ADMINISTRATOR
        ]);
 
        $response->assertStatus(200)->assertJsonStructure([
            'access_token',
        ]);

        $user = User::where('email', 'valid@email.com')->firstOrFail();

        $this->assertSame([Role::ROLE_STUDENT], $user->getRoleNames()->all());
        $this->assertFalse($user->hasRole(Role::ROLE_ADMINISTRATOR));
        $this->assertFalse($user->can(P::USERS_VIEW));
        $this->assertFalse($user->can(P::STUDENTS_ALL));
    }

    public function test_api_registration_ignores_requested_director_role_and_assigns_student()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Director Request',
            'email' => 'director-request@email.com',
            'password' => 'ValidPassword',
            'password_confirmation' => 'ValidPassword',
            'role_id' => Role::ROLE_DIRECTOR,
        ]);

        $response->assertStatus(200)->assertJsonStructure([
            'access_token',
        ]);

        $user = User::where('email', 'director-request@email.com')->firstOrFail();

        $this->assertSame([Role::ROLE_STUDENT], $user->getRoleNames()->all());
        $this->assertSame(Role::ROLE_STUDENT, $user->user_type);
        $this->assertFalse($user->hasRole(Role::ROLE_DIRECTOR));
    }
 
    public function test_api_registration_assigns_student_role()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Valid name',
            'email' => 'student@email.com',
            'password' => 'ValidPassword',
            'password_confirmation' => 'ValidPassword',
        ]);
 
        $response->assertStatus(200)->assertJsonStructure([
            'access_token',
        ]);

        $user = User::where('email', 'student@email.com')->firstOrFail();

        $this->assertSame([Role::ROLE_STUDENT], $user->getRoleNames()->all());
        $this->assertTrue($user->can(P::STUDENTS_OWN));
        $this->assertFalse($user->can(P::STUDENTS_CREATE));
    }
}
