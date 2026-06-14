<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => \App\Models\Role::ROLE_ADMINISTRATOR, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => \App\Models\Role::ROLE_OWNER, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => \App\Models\Role::ROLE_USER, 'guard_name' => 'web']);
    }
}
