<?php

namespace Database\Seeders;

use App\Models\Role as SchoolRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([SchoolRole::ROLE_ADMINISTRATOR, SchoolRole::ROLE_DIRECTOR, SchoolRole::ROLE_SECRETARY, SchoolRole::ROLE_PROFESSOR, SchoolRole::ROLE_STUDENT] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
