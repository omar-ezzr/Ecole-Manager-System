<?php

namespace Database\Seeders;

use App\Support\SchoolPermissions;
use Illuminate\Database\Seeder;
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
        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $all = Permission::whereIn('name', $permissions)->get();
        $role = fn (string $name) => Role::findByName($name, 'web');
        $role(\App\Models\Role::ROLE_ADMINISTRATOR)->syncPermissions($all);
        $role(\App\Models\Role::ROLE_DIRECTOR)->syncPermissions($all->whereIn('name', [
            SchoolPermissions::DASHBOARD_GLOBAL,
            SchoolPermissions::STUDENTS_ALL,
            SchoolPermissions::SCHOOLS_VIEW,
            SchoolPermissions::DEPARTMENTS_VIEW,
            SchoolPermissions::CLASSROOMS_ALL,
            SchoolPermissions::SUBJECTS_VIEW,
            SchoolPermissions::ACADEMIC_YEARS_VIEW,
            SchoolPermissions::SEMESTERS_VIEW,
            SchoolPermissions::TEACHING_ASSIGNMENTS_VIEW_ALL,
            SchoolPermissions::GRADES_ALL,
            SchoolPermissions::ATTENDANCE_VIEW_ALL,
            SchoolPermissions::HEALTH_VIEW,
            SchoolPermissions::REPORTS_VIEW,
        ]));
        $role(\App\Models\Role::ROLE_SECRETARY)->syncPermissions($all->whereIn('name', [
            SchoolPermissions::DASHBOARD_GLOBAL,
            SchoolPermissions::STUDENTS_ALL,
            SchoolPermissions::SCHOOLS_VIEW,
            SchoolPermissions::DEPARTMENTS_VIEW,
            SchoolPermissions::CLASSROOMS_ALL,
            SchoolPermissions::SUBJECTS_VIEW,
            SchoolPermissions::ACADEMIC_YEARS_VIEW,
            SchoolPermissions::SEMESTERS_VIEW,
            SchoolPermissions::TEACHING_ASSIGNMENTS_VIEW_ALL,
            SchoolPermissions::GRADES_ALL,
            SchoolPermissions::HEALTH_VIEW,
            SchoolPermissions::REPORTS_VIEW,
        ]));
        $role(\App\Models\Role::ROLE_PROFESSOR)->syncPermissions($all->whereIn('name', [
            SchoolPermissions::DASHBOARD_SCOPED,
            SchoolPermissions::STUDENTS_ASSIGNED,
            SchoolPermissions::CLASSROOMS_ASSIGNED,
            SchoolPermissions::SUBJECTS_VIEW,
            SchoolPermissions::ACADEMIC_YEARS_VIEW,
            SchoolPermissions::SEMESTERS_VIEW,
            SchoolPermissions::TEACHING_ASSIGNMENTS_VIEW_OWN,
            SchoolPermissions::GRADES_ASSIGNED,
            SchoolPermissions::GRADES_MANAGE_ASSIGNED,
            SchoolPermissions::ATTENDANCE_VIEW_ASSIGNED,
            SchoolPermissions::ATTENDANCE_MANAGE_ASSIGNED,
        ]));
        $role(\App\Models\Role::ROLE_STUDENT)->syncPermissions($all->whereIn('name', [
            SchoolPermissions::DASHBOARD_SCOPED,
            SchoolPermissions::STUDENTS_OWN,
            SchoolPermissions::ACADEMIC_YEARS_VIEW,
            SchoolPermissions::SEMESTERS_VIEW,
            SchoolPermissions::GRADES_OWN,
        ]));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
