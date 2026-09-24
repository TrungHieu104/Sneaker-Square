<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id('return_id');
            $table->unsignedBigInteger('order_id')->unique();
            $table->foreign('order_id')->references('order_id')->on('order')->onDelete('cascade')->onUpdate('cascade');
            $table->string('status', 20)->default('requested');
            $table->string('reason', 50);
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->text('refund_info');
            $table->text('reject_reason')->nullable();
            $table->string('return_shipping_code', 50)->nullable()->unique();
            $table->string('return_shipping_status', 50)->nullable();
            $table->unsignedInteger('refund_amount')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_returns');
    }
};
