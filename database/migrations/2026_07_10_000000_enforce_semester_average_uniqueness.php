<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_grades', function (Blueprint $table) {
            $table->unsignedTinyInteger('semester_average_slot')
                ->nullable()
                ->after('subject_id');
        });

        /*
         * Remove duplicate semester-average rows before adding the constraint.
         * A semester average is represented by subject_id = null.
         */
        $duplicateGroups = DB::table('student_grades')
            ->select('student_id', 'semester_id')
            ->whereNull('subject_id')
            ->groupBy('student_id', 'semester_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $gradeToKeep = DB::table('student_grades')
                ->where('student_id', $group->student_id)
                ->where('semester_id', $group->semester_id)
                ->whereNull('subject_id')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->value('id');

            DB::table('student_grades')
                ->where('student_id', $group->student_id)
                ->where('semester_id', $group->semester_id)
                ->whereNull('subject_id')
                ->where('id', '!=', $gradeToKeep)
                ->delete();
        }

        /*
         * Subject grades keep this field null.
         * Semester-average rows receive the value 1.
         *
         * MySQL permits multiple null values in a unique index, so normal
         * subject-grade rows are unaffected while only one average row is
         * permitted per student and semester.
         */
        DB::table('student_grades')
            ->whereNull('subject_id')
            ->update([
                'semester_average_slot' => 1,
            ]);

        Schema::table('student_grades', function (Blueprint $table) {
            $table->unique(
                [
                    'student_id',
                    'semester_id',
                    'semester_average_slot',
                ],
                'student_grades_one_semester_average_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('student_grades', function (Blueprint $table) {
            $table->dropUnique(
                'student_grades_one_semester_average_unique'
            );

            $table->dropColumn('semester_average_slot');
        });
    }
};
