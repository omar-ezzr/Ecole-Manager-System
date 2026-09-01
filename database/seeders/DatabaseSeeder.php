<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(AdminUserSeeder::class);
        $this->call(AcademicYearSeeder::class);
        $this->call(SemesterSeeder::class);
        $this->call(SubjectSeeder::class);
        $this->call(SchoolStructureSeeder::class);
        $this->call(MoroccanStudentSeeder::class);
        $this->call(DemoHealthRecordSeeder::class);
        $this->call(MoroccanDemoUserSeeder::class);
        $this->call(MoroccanAcademicRecordSeeder::class);
        $this->call(MoroccanAttendanceSeeder::class);
    }
}
