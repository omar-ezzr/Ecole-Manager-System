<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $activeAcademicYearId = DB::table('academic_years')
            ->where('is_active', true)
            ->orderByDesc('starts_at')
            ->value('id');

        if ($activeAcademicYearId === null) {
            throw new RuntimeException('Student enrollments require an active academic year for the existing-student backfill.');
        }

        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->date('enrolled_at');
            $table->date('left_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['student_id', 'classroom_id', 'academic_year_id'],
                'student_enrollments_unique_context'
            );
            $table->index(
                ['classroom_id', 'academic_year_id'],
                'student_enrollments_classroom_year_index'
            );
            $table->index(
                ['student_id', 'left_at'],
                'student_enrollments_active_lookup_index'
            );
        });

        $now = now();
        $enrolledAt = $now->toDateString();

        DB::table('students')
            ->select(['id', 'classroom_id'])
            ->orderBy('id')
            ->chunkById(500, function ($students) use ($activeAcademicYearId, $enrolledAt, $now): void {
                DB::table('student_enrollments')->insert(
                    $students->map(fn (object $student) => [
                        'student_id' => $student->id,
                        'classroom_id' => $student->classroom_id,
                        'academic_year_id' => $activeAcademicYearId,
                        'enrolled_at' => $enrolledAt,
                        'left_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
