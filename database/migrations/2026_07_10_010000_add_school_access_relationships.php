<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->unique()->after('id')->constrained('students')->nullOnDelete();
        });
        Schema::create('classroom_professor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professor_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['classroom_id', 'professor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_professor');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('student_id'));
    }
};
