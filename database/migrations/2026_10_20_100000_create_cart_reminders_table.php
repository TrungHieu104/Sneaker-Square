<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('cart_key', 64);
            $table->unsignedTinyInteger('step');
            $table->json('lines');
            $table->unsignedInteger('cart_value')->default(0);
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->foreign('coupon_id')->references('coupon_id')->on('coupon')->onDelete('set null')->onUpdate('cascade');
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->foreign('sent_by')->references('user_id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->char('token', 40)->unique();
            $table->timestamp('sent_at');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamps();

            $table->unique(['cart_key', 'step']);
            $table->index('sent_at');
        });

        Schema::create('cart_reminder_opt_outs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_reminder_opt_outs');
        Schema::dropIfExists('cart_reminders');
    }
};
