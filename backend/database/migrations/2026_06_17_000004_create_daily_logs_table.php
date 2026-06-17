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
        Schema::create('daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            $table->unsignedInteger('total_calories')->default(0);
            $table->decimal('total_protein_g', 8, 2)->default(0);
            $table->decimal('total_carbs_g', 8, 2)->default(0);
            $table->decimal('total_fat_g', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->unique(['user_id', 'log_date']);
            $table->index(['user_id', 'log_date']);
        });

        DB::statement('ALTER TABLE daily_logs ADD CONSTRAINT daily_logs_total_calories_check CHECK (total_calories >= 0)');
        DB::statement('ALTER TABLE daily_logs ADD CONSTRAINT daily_logs_total_protein_g_check CHECK (total_protein_g >= 0)');
        DB::statement('ALTER TABLE daily_logs ADD CONSTRAINT daily_logs_total_carbs_g_check CHECK (total_carbs_g >= 0)');
        DB::statement('ALTER TABLE daily_logs ADD CONSTRAINT daily_logs_total_fat_g_check CHECK (total_fat_g >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_logs');
    }
};
