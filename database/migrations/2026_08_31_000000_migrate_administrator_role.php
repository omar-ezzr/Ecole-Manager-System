<?php

use App\Models\Role as SchoolRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const OLD_NAME = 'Administrator';

    public function up(): void
    {
        DB::transaction(function (): void {
            $tables = config('permission.table_names');
            $roleKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
            $modelKey = config('permission.column_names.model_morph_key') ?: 'model_id';
            $permissionKey = config('permission.column_names.permission_pivot_key') ?: 'permission_id';

            $oldRole = DB::table($tables['roles'])
                ->where('name', self::OLD_NAME)
                ->where('guard_name', 'web')
                ->first();
            $newRole = DB::table($tables['roles'])
                ->where('name', SchoolRole::ROLE_ADMINISTRATOR)
                ->where('guard_name', 'web')
                ->first();

            if (! $oldRole) {
                return;
            }

            if (! $newRole) {
                DB::table($tables['roles'])->where('id', $oldRole->id)->update([
                    'name' => SchoolRole::ROLE_ADMINISTRATOR,
                ]);
                $newRole = (object) ['id' => $oldRole->id];
            } else {
                foreach (DB::table($tables['model_has_roles'])->where($roleKey, $oldRole->id)->get() as $assignment) {
                    DB::table($tables['model_has_roles'])->updateOrInsert([
                        $roleKey => $newRole->id,
                        'model_type' => $assignment->model_type,
                        $modelKey => $assignment->{$modelKey},
                    ]);
                }

                foreach (DB::table($tables['role_has_permissions'])->where($roleKey, $oldRole->id)->get() as $permission) {
                    DB::table($tables['role_has_permissions'])->updateOrInsert([
                        $permissionKey => $permission->{$permissionKey},
                        $roleKey => $newRole->id,
                    ]);
                }

                DB::table($tables['model_has_roles'])->where($roleKey, $oldRole->id)->delete();
                DB::table($tables['role_has_permissions'])->where($roleKey, $oldRole->id)->delete();
                DB::table($tables['roles'])->where('id', $oldRole->id)->delete();
            }

            $administratorIds = DB::table($tables['model_has_roles'])
                ->where($roleKey, $newRole->id)
                ->where('model_type', User::class)
                ->pluck($modelKey);

            DB::table('users')->whereIn('id', $administratorIds)->update([
                'user_type' => SchoolRole::ROLE_ADMINISTRATOR,
            ]);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Splitting merged role assignments during rollback would be destructive.
    }
};
