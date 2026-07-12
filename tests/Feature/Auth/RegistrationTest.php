<?php

use App\Livewire\Auth\Register;
use App\Models\Role;
use App\Models\User;
use App\Support\SchoolPermissions as P;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = Livewire::test(Register::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('registered user receives the student role only', function () {
    Livewire::test(Register::class)
        ->set('name', 'Student User')
        ->set('email', 'student@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasNoErrors();

    $user = User::where('email', 'student@example.com')->firstOrFail();

    expect($user->getRoleNames()->all())->toBe([Role::ROLE_STUDENT]);
    expect($user->user_type)->toBe(Role::ROLE_STUDENT);
    expect($user->student_id)->toBeNull();
    expect($user->can(P::STUDENTS_OWN))->toBeTrue();
    expect($user->can(P::STUDENTS_ALL))->toBeFalse();
});

test('registered user does not receive director or administrator permissions', function () {
    Livewire::test(Register::class)
        ->set('name', 'Safe Student')
        ->set('email', 'safe-student@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasNoErrors();

    $user = User::where('email', 'safe-student@example.com')->firstOrFail();

    expect($user->hasRole(Role::ROLE_DIRECTOR))->toBeFalse();
    expect($user->hasRole(Role::ROLE_ADMINISTRATOR))->toBeFalse();
    expect($user->can(P::USERS_VIEW))->toBeFalse();
    expect($user->can(P::USERS_CREATE))->toBeFalse();
    expect($user->can(P::STUDENTS_CREATE))->toBeFalse();
    expect($user->can(P::STUDENTS_UPDATE))->toBeFalse();
    expect($user->can(P::STUDENTS_DELETE))->toBeFalse();
    expect($user->can(P::STUDENTS_ALL))->toBeFalse();
});

test('registered user cannot access user administration or management routes', function () {
    Livewire::test(Register::class)
        ->set('name', 'Restricted Student')
        ->set('email', 'restricted-student@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasNoErrors();

    $user = User::where('email', 'restricted-student@example.com')->firstOrFail();

    $this->actingAs($user)->get('/administration/users')->assertForbidden();

    foreach ([
        'administration.users.create',
        'students.create',
        'classrooms.create',
        'departments.create',
        'schools.create',
        'health-records.create',
    ] as $routeName) {
        $this->actingAs($user)->get(route($routeName))->assertForbidden();
    }
});
