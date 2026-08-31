<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('register route exists and guests can open it', function () {
    expect(Route::has('register'))->toBeTrue();

    $this->get(route('register'))->assertOk();
});

test('login page shows the named registration link', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Create account')
        ->assertSee(route('register'), false);
});

test('self registration creates an inactive student that can login after approval', function () {
    Livewire::test(Register::class)
        ->set('name', 'Public User')
        ->set('email', 'public@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('login', absolute: false));

    $user = User::where('email', 'public@example.com')->firstOrFail();

    expect($user->is_active)->toBeFalse()
        ->and($user->user_type)->toBe(Role::ROLE_STUDENT)
        ->and($user->hasRole(Role::ROLE_STUDENT))->toBeTrue()
        ->and(Hash::check('Password123!', $user->password))->toBeTrue();
    $this->assertGuest();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'Password123!')
        ->call('login')
        ->assertHasErrors(['email'])
        ->assertSee('This account is inactive. Contact an administrator.');

    $this->assertGuest();

    $user->update(['is_active' => true]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'Password123!')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    $this->get(route('dashboard'))->assertOk();
});
