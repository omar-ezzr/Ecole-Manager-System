<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        $now = now();
        $startYear = $now->month >= 8 ? $now->year : $now->year - 1;
        $startsAt = sprintf('%d-09-01', $startYear);
        $endsAt = sprintf('%d-07-31', $startYear + 1);

        DB::table('academic_years')->insert([
            'name' => sprintf('%d-%d', $startYear, $startYear + 1),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Schema::create('teaching_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['professor_id', 'classroom_id', 'subject_id', 'academic_year_id'],
                'teaching_assignments_unique_assignment'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_assignments');
        Schema::dropIfExists('academic_years');
    }
};
