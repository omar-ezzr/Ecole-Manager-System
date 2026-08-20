<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->text('description')->nullable()->after('code');
            $table->boolean('is_active')->default(true)->after('description');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('code')->nullable(false)->change();
            $table->foreignId('semester_id')->nullable()->change();
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->unique('code', 'subjects_code_unique');
        });

        $activeAcademicYearId = DB::table('academic_years')->where('is_active', true)->value('id');

        Schema::table('semesters', function (Blueprint $table) use ($activeAcademicYearId) {
            $table->dropUnique('semesters_code_unique');
            $table->dropUnique('semesters_position_unique');
            $table->foreignId('academic_year_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->date('starts_at')->nullable()->after('name');
            $table->date('ends_at')->nullable()->after('starts_at');
            $table->unsignedTinyInteger('sequence')->nullable()->after('ends_at');
        });

        $academicYear = DB::table('academic_years')->where('id', $activeAcademicYearId)->first();
        $yearStart = \Carbon\Carbon::parse($academicYear->starts_at);
        $yearEnd = \Carbon\Carbon::parse($academicYear->ends_at);
        $totalDays = max(1, $yearStart->diffInDays($yearEnd) + 1);
        $chunk = max(1, intdiv($totalDays, 6));

        DB::table('semesters')->orderBy('position')->get()->values()->each(function (object $semester, int $index) use ($activeAcademicYearId, $yearStart, $yearEnd, $chunk): void {
            $sequence = (int) ($semester->position ?: 1);
            $startsAt = $yearStart->copy()->addDays($chunk * $index);
            $endsAt = $sequence === 6
                ? $yearEnd->copy()
                : $yearStart->copy()->addDays(($chunk * ($index + 1)) - 1);

            DB::table('semesters')->where('id', $semester->id)->update([
                'academic_year_id' => $activeAcademicYearId,
                'starts_at' => $startsAt->toDateString(),
                'ends_at' => $endsAt->toDateString(),
                'sequence' => $sequence,
            ]);
        });

        Schema::table('semesters', function (Blueprint $table) {
            $table->string('code')->nullable(false)->change();
            $table->unsignedTinyInteger('position')->nullable(false)->change();
            $table->foreignId('academic_year_id')->nullable(false)->change();
            $table->date('starts_at')->nullable(false)->change();
            $table->date('ends_at')->nullable(false)->change();
            $table->unsignedTinyInteger('sequence')->nullable(false)->change();
            $table->unique(['academic_year_id', 'name'], 'semesters_year_name_unique');
        });

        Schema::table('student_grades', function (Blueprint $table) {
            $table->foreignId('teaching_assignment_id')->nullable()->after('student_id')->constrained()->restrictOnDelete();
            $table->string('type')->nullable()->after('grade');
            $table->decimal('coefficient', 5, 2)->default(1)->after('type');
        });

        Schema::table('student_grades', function (Blueprint $table) {
            $table->dropUnique('student_grades_student_id_semester_id_subject_id_unique');
            $table->unique(
                ['student_id', 'semester_id', 'teaching_assignment_id'],
                'student_grades_student_semester_assignment_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('student_grades', function (Blueprint $table) {
            $table->dropUnique('student_grades_student_semester_assignment_unique');
            $table->dropConstrainedForeignId('teaching_assignment_id');
            $table->dropColumn(['type', 'coefficient']);
            $table->unique(['student_id', 'semester_id', 'subject_id']);
        });

        Schema::table('semesters', function (Blueprint $table) {
            $table->dropUnique('semesters_year_name_unique');
            $table->dropConstrainedForeignId('academic_year_id');
            $table->dropColumn(['starts_at', 'ends_at', 'sequence']);
            $table->unique('code');
            $table->unique('position');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique('subjects_code_unique');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable(false)->change();
            $table->string('code')->nullable()->change();
            $table->dropColumn(['description', 'is_active']);
        });
    }
};
