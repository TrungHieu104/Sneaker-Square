<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('username', 50)->nullable();
            $table->string('name', 255);
            $table->string('user_img', 255)->nullable();
            $table->string('email', 255)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->string('user_phone', 15)->unique()->nullable();
            $table->boolean('user_status')->default(1);
            $table->boolean('user_role')->default(0);
            $table->string('google_id', 50)->nullable();
            $table->string('facebook_id', 50)->nullable();
            $table->integer('login_attempts')->default(0);
            $table->timestamp('locked_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
