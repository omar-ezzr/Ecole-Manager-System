<?php

use App\Models\Role as SchoolRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const OLD_NAME = 'Service Secrétaire';

    public function up(): void
    {
        DB::transaction(function (): void {
            $tables = $this->tables();
            $roles = $tables['roles'];
            $modelHasRoles = $tables['model_has_roles'];
            $roleHasPermissions = $tables['role_has_permissions'];
            $roleKey = $this->rolePivotKey();

            $oldRole = DB::table($roles)->where('name', self::OLD_NAME)->first();
            $newRole = DB::table($roles)->where('name', SchoolRole::ROLE_SECRETARY)->first();

            if (! $oldRole && ! $newRole) {
                return;
            }

            if ($oldRole && ! $newRole) {
                DB::table($roles)
                    ->where('id', $oldRole->id)
                    ->update(['name' => SchoolRole::ROLE_SECRETARY]);

                return;
            }

            if (! $oldRole || ! $newRole) {
                return;
            }

            foreach (DB::table($modelHasRoles)->where($roleKey, $oldRole->id)->get() as $assignment) {
                $exists = DB::table($modelHasRoles)
                    ->where($roleKey, $newRole->id)
                    ->where('model_type', $assignment->model_type)
                    ->where($this->modelMorphKey(), $assignment->{$this->modelMorphKey()})
                    ->exists();

                if ($exists) {
                    DB::table($modelHasRoles)
                        ->where($roleKey, $oldRole->id)
                        ->where('model_type', $assignment->model_type)
                        ->where($this->modelMorphKey(), $assignment->{$this->modelMorphKey()})
                        ->delete();
                } else {
                    DB::table($modelHasRoles)
                        ->where($roleKey, $oldRole->id)
                        ->where('model_type', $assignment->model_type)
                        ->where($this->modelMorphKey(), $assignment->{$this->modelMorphKey()})
                        ->update([$roleKey => $newRole->id]);
                }
            }

            foreach (DB::table($roleHasPermissions)->where($roleKey, $oldRole->id)->get() as $permission) {
                $permissionKey = $this->permissionPivotKey();
                $exists = DB::table($roleHasPermissions)
                    ->where($roleKey, $newRole->id)
                    ->where($permissionKey, $permission->{$permissionKey})
                    ->exists();

                if (! $exists) {
                    DB::table($roleHasPermissions)->insert([
                        $permissionKey => $permission->{$permissionKey},
                        $roleKey => $newRole->id,
                    ]);
                }
            }

            DB::table($roleHasPermissions)->where($roleKey, $oldRole->id)->delete();
            DB::table($roles)->where('id', $oldRole->id)->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $roles = $this->tables()['roles'];
            $oldExists = DB::table($roles)->where('name', self::OLD_NAME)->exists();
            $newRole = DB::table($roles)->where('name', SchoolRole::ROLE_SECRETARY)->first();

            /*
             * Rollback is intentionally non-destructive: it renames the current
             * role back only when the historical name is absent, and never splits
             * merged user-role assignments or permissions.
             */
            if (! $oldExists && $newRole) {
                DB::table($roles)
                    ->where('id', $newRole->id)
                    ->update(['name' => self::OLD_NAME]);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function tables(): array
    {
        return config('permission.table_names');
    }

    private function rolePivotKey(): string
    {
        return config('permission.column_names.role_pivot_key') ?: 'role_id';
    }

    private function permissionPivotKey(): string
    {
        return config('permission.column_names.permission_pivot_key') ?: 'permission_id';
    }

    private function modelMorphKey(): string
    {
        return config('permission.column_names.model_morph_key') ?: 'model_id';
    }
};
