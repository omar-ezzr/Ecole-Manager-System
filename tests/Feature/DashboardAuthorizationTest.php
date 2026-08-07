<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_output_does_not_expose_removed_domains(): void
    {
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($director)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Compagnie')
            ->assertDontSee('Groupement')
            ->assertDontSee('CIE')
            ->assertDontSee('GPT')
            ->assertDontSee('compagnie_id')
            ->assertDontSee('groupement_id');
    }

    public function test_director_dashboard_is_read_only(): void
    {
        $director = User::factory()->create();
        $director->assignRole(Role::ROLE_DIRECTOR);

        $this->actingAs($director)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('View students')
            ->assertDontSee('Add student')
            ->assertDontSee('Create manually')
            ->assertDontSee('Create record');
    }
}
