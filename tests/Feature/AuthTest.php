<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_registration_is_unavailable(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Public API User',
            'email' => 'public-api@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'public-api@example.com']);
    }
}
