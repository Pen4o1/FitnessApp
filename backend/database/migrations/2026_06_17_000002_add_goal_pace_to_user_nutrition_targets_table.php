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
        Schema::table('user_nutrition_targets', function (Blueprint $table) {
            $table->string('goal_pace', 20)->default('moderate')->after('goal_type');
        });

        DB::statement("ALTER TABLE user_nutrition_targets ADD CONSTRAINT user_nutrition_targets_goal_pace_check CHECK (goal_pace IN ('slow', 'moderate', 'aggressive'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE user_nutrition_targets DROP CONSTRAINT IF EXISTS user_nutrition_targets_goal_pace_check');

        Schema::table('user_nutrition_targets', function (Blueprint $table) {
            $table->dropColumn('goal_pace');
        });
    }
};
