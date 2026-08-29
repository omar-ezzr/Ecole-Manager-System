<?php

use App\Livewire\Auth\Register;
use App\Models\Role as SchoolRole;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('registration screen can be rendered', function () {
    $this->get('/register')
        ->assertOk();
});

test('guests can create an inactive account', function () {
    Livewire::test(Register::class)
        ->set('name', 'Public User')
        ->set('email', 'public@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('login', absolute: false));

    $user = User::where('email', 'public@example.com')->firstOrFail();

    expect($user->is_active)->toBeFalse();
    expect($user->hasRole(SchoolRole::ROLE_STUDENT))->toBeTrue();

    $this->assertGuest();
});

test('inactive self registered users cannot login', function () {
    $user = User::factory()->create([
        'email' => 'pending@example.com',
        'is_active' => false,
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('login page shows registration link', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Create account');
});