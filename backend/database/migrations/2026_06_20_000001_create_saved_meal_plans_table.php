<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_meal_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('plan_date');
            $table->unsignedTinyInteger('meals_count');
            $table->unsignedInteger('total_calories');
            $table->decimal('total_protein_g', 8, 2);
            $table->decimal('total_carbs_g', 8, 2);
            $table->decimal('total_fat_g', 8, 2);
            $table->boolean('within_target');
            $table->json('plan_data');
            $table->timestampTz('logged_to_diary_at')->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'plan_date']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_meal_plans');
    }
};
