<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('public registration is unavailable', function () {
    expect(Route::has('register'))->toBeFalse();

    $this->get('/register')->assertNotFound();
});

test('guests cannot create an account through the former registration endpoint', function () {
    $this->post('/register', [
        'name' => 'Public User',
        'email' => 'public@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertNotFound();

    $this->assertDatabaseMissing('users', ['email' => 'public@example.com']);
});

test('guest pages do not show registration links', function () {
    $this->get('/login')->assertOk()->assertDontSee('Sign up')->assertDontSee('Create account');
    $this->get('/')->assertOk()->assertDontSee('Register');

    expect(User::where('email', 'public@example.com')->doesntExist())->toBeTrue();
});
