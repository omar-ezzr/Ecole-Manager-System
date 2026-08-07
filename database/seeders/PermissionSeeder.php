<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Support\SchoolPermissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = SchoolPermissions::all();
        foreach ($permissions as $name) Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        $all = Permission::whereIn('name', $permissions)->get();
        $role = fn (string $name) => Role::findByName($name, 'web');
        $role(\App\Models\Role::ROLE_ADMINISTRATOR)->syncPermissions($all);
        $role(\App\Models\Role::ROLE_DIRECTOR)->syncPermissions($all->whereIn('name', [SchoolPermissions::DASHBOARD_GLOBAL, SchoolPermissions::STUDENTS_ALL, SchoolPermissions::SCHOOLS_VIEW, SchoolPermissions::DEPARTMENTS_VIEW, SchoolPermissions::CLASSROOMS_ALL, SchoolPermissions::GRADES_ALL, SchoolPermissions::HEALTH_VIEW, SchoolPermissions::REPORTS_VIEW]));
        $role(\App\Models\Role::ROLE_SECRETARY)->syncPermissions($all->whereIn('name', [SchoolPermissions::DASHBOARD_GLOBAL, SchoolPermissions::STUDENTS_ALL, SchoolPermissions::STUDENTS_CREATE, SchoolPermissions::STUDENTS_UPDATE, SchoolPermissions::STUDENTS_IMPORT, SchoolPermissions::SCHOOLS_VIEW, SchoolPermissions::DEPARTMENTS_VIEW, SchoolPermissions::CLASSROOMS_ALL, SchoolPermissions::GRADES_ALL, SchoolPermissions::HEALTH_VIEW, SchoolPermissions::HEALTH_MANAGE]));
        $role(\App\Models\Role::ROLE_PROFESSOR)->syncPermissions($all->whereIn('name', [SchoolPermissions::DASHBOARD_SCOPED, SchoolPermissions::STUDENTS_ASSIGNED, SchoolPermissions::CLASSROOMS_ASSIGNED, SchoolPermissions::GRADES_ASSIGNED, SchoolPermissions::GRADES_MANAGE_ASSIGNED]));
        $role(\App\Models\Role::ROLE_STUDENT)->syncPermissions($all->whereIn('name', [SchoolPermissions::DASHBOARD_SCOPED, SchoolPermissions::STUDENTS_OWN, SchoolPermissions::GRADES_OWN]));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
    
}
