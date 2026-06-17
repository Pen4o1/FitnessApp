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
        Schema::create('user_nutrition_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('activity_level', 30);
            $table->string('goal_type', 20);
            $table->decimal('target_weight_kg', 5, 2);
            $table->unsignedInteger('calorie_target');
            $table->unsignedInteger('protein_target_g');
            $table->unsignedInteger('carbs_target_g');
            $table->unsignedInteger('fat_target_g');
            $table->unsignedInteger('bmr')->nullable();
            $table->unsignedInteger('tdee')->nullable();
            $table->unsignedInteger('estimated_days_to_goal')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('effective_from');
            $table->timestampsTz();

            $table->index(['user_id', 'effective_from']);
        });

        DB::statement("ALTER TABLE user_nutrition_targets ADD CONSTRAINT user_nutrition_targets_activity_level_check CHECK (activity_level IN ('sedentary', 'lightly_active', 'moderately_active', 'very_active', 'extra_active'))");
        DB::statement("ALTER TABLE user_nutrition_targets ADD CONSTRAINT user_nutrition_targets_goal_type_check CHECK (goal_type IN ('lose', 'maintain', 'gain'))");
        DB::statement('ALTER TABLE user_nutrition_targets ADD CONSTRAINT user_nutrition_targets_target_weight_kg_check CHECK (target_weight_kg > 0)');
        DB::statement('CREATE UNIQUE INDEX user_nutrition_targets_user_id_active_unique ON user_nutrition_targets (user_id) WHERE is_active = true');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_nutrition_targets');
    }
};
