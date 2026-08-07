<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['eleves', 'students'] as $table) {
            $this->dropForeignIdIfExists($table, 'compagnie_id');
        }

        foreach (['suivi_sanitaires', 'health_records'] as $table) {
            $this->dropForeignIdIfExists($table, 'compagnie_id');
        }

        foreach (['ecoles', 'schools'] as $table) {
            $this->dropForeignIdIfExists($table, 'groupement_id');
        }

        if (Schema::hasTable('compagnies')) {
            $this->dropForeignIdIfExists('compagnies', 'groupement_id');
            Schema::dropIfExists('compagnies');
        }

        Schema::dropIfExists('groupements');
    }

    public function down(): void
    {
        Schema::create('groupements', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('compagnies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('groupement_id')->nullable()->constrained('groupements')->nullOnDelete();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        foreach (['eleves', 'students'] as $table) {
            $this->addCompagnieForeignIdIfPossible($table);
        }

        foreach (['suivi_sanitaires', 'health_records'] as $table) {
            $this->addCompagnieForeignIdIfPossible($table);
        }

        foreach (['ecoles', 'schools'] as $table) {
            $this->addGroupementForeignIdIfPossible($table);
        }
    }

    private function dropForeignIdIfExists(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $this->dropForeignKeysForColumn($table, $column);

        Schema::table($table, function (Blueprint $table) use ($column) {
            $table->dropColumn($column);
        });
    }

    private function dropForeignKeysForColumn(string $table, string $column): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $constraints = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME');

        foreach ($constraints as $constraint) {
            Schema::table($table, function (Blueprint $table) use ($constraint) {
                $table->dropForeign($constraint);
            });
        }
    }

    private function addCompagnieForeignIdIfPossible(string $table): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'compagnie_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->foreignId('compagnie_id')->nullable()->constrained('compagnies')->nullOnDelete();
        });
    }

    private function addGroupementForeignIdIfPossible(string $table): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'groupement_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->foreignId('groupement_id')->nullable()->constrained('groupements')->nullOnDelete();
        });
    }
};
