<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('address');
        });

        Schema::create('classroom_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['classroom_id', 'subject_id', 'academic_year_id'],
                'classroom_subjects_unique_context'
            );
            $table->index(
                ['academic_year_id', 'classroom_id'],
                'classroom_subjects_year_classroom_index'
            );
        });

        DB::table('subjects')->whereNotNull('semester_id')->update(['semester_id' => null]);

        DB::table('teaching_assignments')
            ->select(['classroom_id', 'subject_id', 'academic_year_id'])
            ->distinct()
            ->orderBy('classroom_id')
            ->chunk(500, function ($assignments): void {
                $now = now();
                $rows = $assignments->map(fn (object $assignment) => [
                    'classroom_id' => $assignment->classroom_id,
                    'subject_id' => $assignment->subject_id,
                    'academic_year_id' => $assignment->academic_year_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                if ($rows !== []) {
                    DB::table('classroom_subjects')->upsert(
                        $rows,
                        ['classroom_id', 'subject_id', 'academic_year_id'],
                        ['updated_at']
                    );
                }
            });

        Schema::table('semesters', function (Blueprint $table) {
            $table->unique(['academic_year_id', 'sequence'], 'semesters_year_sequence_unique');
        });

        Schema::table('student_grades', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();

            $table->dropForeign(['subject_id']);
            $table->foreign('subject_id')->references('id')->on('subjects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_grades', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->foreign('subject_id')->references('id')->on('subjects')->nullOnDelete();

            $table->dropForeign(['semester_id']);
            $table->foreign('semester_id')->references('id')->on('semesters')->cascadeOnDelete();
        });

        Schema::table('semesters', function (Blueprint $table) {
            $table->dropUnique('semesters_year_sequence_unique');
        });

        Schema::dropIfExists('classroom_subjects');

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
