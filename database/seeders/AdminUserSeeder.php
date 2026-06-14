<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::updateOrCreate([
            'email' => 'admin@school.local',
        ], [
            'name' => 'Administrator',
            'password' => bcrypt('SuperSecretPassword'),
            'email_verified_at' => now(),
 
            // no more role_id here
        ]);
 
        $user->assignRole('Administrator');
    }
}
