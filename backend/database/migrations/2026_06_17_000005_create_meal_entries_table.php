<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_log_id')->constrained()->cascadeOnDelete();
            $table->string('meal_type', 20);
            $table->string('name', 100)->nullable();
            $table->timestampTz('logged_at');
            $table->timestampsTz();

            $table->index(['daily_log_id', 'meal_type']);
        });

        DB::statement("ALTER TABLE meal_entries ADD CONSTRAINT meal_entries_meal_type_check CHECK (meal_type IN ('breakfast', 'lunch', 'dinner', 'snack', 'other'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meal_entries');
    }
};
