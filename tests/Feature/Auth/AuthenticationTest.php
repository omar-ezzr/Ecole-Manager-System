<?php

use App\Livewire\Auth\Login;
use App\Models\Role;
use App\Models\User;
use App\Support\SchoolPermissions;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('login screen can be rendered', function () {
    $this->get('/login')->assertOk();
});

test('active users with valid credentials authenticate and reach their authorized dashboard', function () {
    $user = User::factory()->create([
        'user_type' => Role::ROLE_DIRECTOR,
        'is_active' => true,
    ]);
    $user->assignRole(Role::ROLE_DIRECTOR);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    expect($user->can(SchoolPermissions::DASHBOARD_GLOBAL))->toBeTrue();
    $this->get(route('dashboard'))->assertOk();
});

test('active users cannot authenticate with an invalid password', function () {
    $user = User::factory()->create(['is_active' => true]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email'])
        ->assertSee(__('auth.failed'));

    $this->assertGuest();
});

test('inactive users cannot authenticate with a valid password', function () {
    $user = User::factory()->create(['is_active' => false]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors(['email'])
        ->assertSee('This account is inactive. Contact an administrator.');

    $this->assertGuest();
});

test('login rate limiting displays a validation message', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        Livewire::test(Login::class)
            ->set('email', 'limited@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    Livewire::test(Login::class)
        ->set('email', 'limited@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email'])
        ->assertSee('Too many login attempts');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
