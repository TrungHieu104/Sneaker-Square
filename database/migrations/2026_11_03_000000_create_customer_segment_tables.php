<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_segments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('segment', 20)->index();
            $table->unsignedTinyInteger('recency_score');
            $table->unsignedTinyInteger('frequency_score');
            $table->unsignedTinyInteger('monetary_score');
            $table->date('last_order_on');
            $table->unsignedInteger('order_count');
            $table->unsignedBigInteger('total_spent');
            $table->timestamp('computed_at');
        });

        Schema::create('order_cashbacks', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->primary();
            $table->foreign('order_id')->references('order_id')->on('order')->onDelete('cascade')->onUpdate('cascade');
            $table->string('segment', 20);
            $table->unsignedTinyInteger('percent');
            $table->unsignedInteger('cap')->nullable();
            $table->unsignedInteger('amount');
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('segment', 20);
            $table->unsignedTinyInteger('percent');
            $table->unsignedInteger('min_order')->default(0);
            $table->unsignedInteger('max_discount')->nullable();
            $table->unsignedTinyInteger('days');
            $table->string('subject', 150);
            $table->text('message');
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->foreign('sent_by')->references('user_id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->timestamps();
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->foreign('coupon_id')->references('coupon_id')->on('coupon')->onDelete('set null')->onUpdate('cascade');
            $table->char('token', 40)->unique();
            $table->timestamp('sent_at');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'user_id']);
            $table->index(['user_id', 'sent_at']);
        });

        Schema::create('campaign_opt_outs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_opt_outs');
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('order_cashbacks');
        Schema::dropIfExists('customer_segments');
    }
};
