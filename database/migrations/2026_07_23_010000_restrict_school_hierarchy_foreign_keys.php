<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * The repository's original hierarchy migration used cascading deletes
         * from schools to departments to classrooms to students. This migration
         * intentionally changes parent hierarchy deletes to restrict so parents
         * with child records cannot be removed accidentally.
         */
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->foreign('school_id')->references('id')->on('schools')->restrictOnDelete();
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['classroom_id']);
            $table->foreign('classroom_id')->references('id')->on('classrooms')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        /*
         * Rollback restores the repository-defined original cascade behavior.
         * It does not infer or preserve environment-specific manual FK changes;
         * production rollback should be treated as a deliberate schema change.
         */
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['classroom_id']);
            $table->foreign('classroom_id')->references('id')->on('classrooms')->cascadeOnDelete();
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
        });
    }
};
