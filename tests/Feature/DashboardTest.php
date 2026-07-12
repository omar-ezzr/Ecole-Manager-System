<?php

use App\Models\Role;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::ROLE_STUDENT);

    $this->actingAs($user);

    $this->get('/dashboard')->assertStatus(200);
});
