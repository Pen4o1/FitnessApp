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
        Schema::create('food_log_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_entry_id')->constrained()->cascadeOnDelete();
            $table->string('food_name');
            $table->string('brand_name')->nullable();
            $table->string('external_food_id', 100)->nullable();
            $table->string('external_source', 30);
            $table->decimal('quantity', 8, 3);
            $table->string('serving_unit', 50);
            $table->string('serving_description')->nullable();
            $table->unsignedInteger('calories');
            $table->decimal('protein_g', 8, 2);
            $table->decimal('carbs_g', 8, 2);
            $table->decimal('fat_g', 8, 2);
            $table->jsonb('source_metadata')->nullable();
            $table->timestampsTz();

            $table->index('meal_entry_id');
        });

        DB::statement("ALTER TABLE food_log_items ADD CONSTRAINT food_log_items_external_source_check CHECK (external_source IN ('fatsecret', 'barcode', 'manual', 'custom'))");
        DB::statement('ALTER TABLE food_log_items ADD CONSTRAINT food_log_items_quantity_check CHECK (quantity > 0)');
        DB::statement('ALTER TABLE food_log_items ADD CONSTRAINT food_log_items_calories_check CHECK (calories >= 0)');
        DB::statement('ALTER TABLE food_log_items ADD CONSTRAINT food_log_items_protein_g_check CHECK (protein_g >= 0)');
        DB::statement('ALTER TABLE food_log_items ADD CONSTRAINT food_log_items_carbs_g_check CHECK (carbs_g >= 0)');
        DB::statement('ALTER TABLE food_log_items ADD CONSTRAINT food_log_items_fat_g_check CHECK (fat_g >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_log_items');
    }
};
