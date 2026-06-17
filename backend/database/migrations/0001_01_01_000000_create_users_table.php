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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->unique();
            $table->timestampTz('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('birthdate')->nullable();
            $table->decimal('current_weight_kg', 5, 2)->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->string('google_id', 255)->nullable()->unique();
            $table->string('avatar_path', 500)->nullable();
            $table->timestampTz('profile_completed_at')->nullable();
            $table->rememberToken();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_gender_check CHECK (gender IS NULL OR gender IN ('male', 'female', 'other', 'prefer_not_to_say'))");
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_current_weight_kg_check CHECK (current_weight_kg IS NULL OR current_weight_kg > 0)');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_height_cm_check CHECK (height_cm IS NULL OR height_cm > 0)');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestampTz('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
