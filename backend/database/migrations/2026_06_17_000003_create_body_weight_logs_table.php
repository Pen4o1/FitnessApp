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
        Schema::create('body_weight_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight_kg', 5, 2);
            $table->timestampTz('recorded_at');
            $table->string('source', 30);
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['user_id', 'recorded_at']);
        });

        DB::statement("ALTER TABLE body_weight_logs ADD CONSTRAINT body_weight_logs_source_check CHECK (source IN ('manual', 'onboarding', 'goal_update'))");
        DB::statement('ALTER TABLE body_weight_logs ADD CONSTRAINT body_weight_logs_weight_kg_check CHECK (weight_kg > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('body_weight_logs');
    }
};
