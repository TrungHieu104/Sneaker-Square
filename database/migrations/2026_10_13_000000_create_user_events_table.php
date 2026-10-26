<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_events', function (Blueprint $table) {
            $table->id('event_id');
            $table->char('session_id', 40);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->string('event_type', 32);
            $table->unsignedBigInteger('pro_id')->nullable();
            $table->unsignedInteger('size_id')->nullable();
            $table->unsignedInteger('color_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->json('meta')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->string('device', 16)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['event_type', 'created_at']);
            $table->index(['pro_id', 'event_type']);
            $table->index('session_id');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_events');
    }
};
