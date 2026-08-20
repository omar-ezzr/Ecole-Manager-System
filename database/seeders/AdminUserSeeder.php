<?php

namespace Database\Seeders;

use App\Models\Role as SchoolRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $password = env('ADMIN_PASSWORD');
        $user = User::firstOrNew(['email' => 'admin@gmail.com']);

        $user->name = SchoolRole::ROLE_ADMINISTRATOR;
        $user->user_type = SchoolRole::ROLE_ADMINISTRATOR;
        $user->is_active = true;
        $user->email_verified_at ??= now();

        if (! $user->exists) {
            if (! $password && app()->environment('production')) {
                $this->command?->warn('ADMIN_PASSWORD is missing; administrator creation was skipped.');

                return;
            }

            $user->password = Hash::make($password ?? 'ChangeMe123!');
        } elseif ($password && ! Hash::check($password, $user->password)) {
            $user->password = Hash::make($password);
        }

        $user->save();
        $user->syncRoles(SchoolRole::ROLE_ADMINISTRATOR);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
