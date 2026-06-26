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
        Schema::create('order', function (Blueprint $table) {
            $table->id('order_id');
            $table->string('order_code', 50)->unique();
            $table->string('order_name', 255);
            $table->string('order_email', 255);
            $table->string('order_address', 255);
            $table->string('order_local', 300);
            $table->string('order_phone', 15);
            $table->unsignedInteger('order_delivery_fee');
            $table->unsignedInteger('order_coupon_value')->default(0);
            $table->unsignedInteger('order_total');
            $table->string('order_payment',50);
            $table->boolean('order_payment_status');
            $table->dateTime('order_payment_time')->nullable();
            $table->longText('order_payment_url')->nullable();
            $table->boolean('order_delivery_status')->default(0);
            $table->date('order_date');
            $table->boolean('order_status')->default(0);
            $table->longText('note_customer')->nullable();
            $table->longText('note_admin')->nullable();
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->foreign('coupon_id')->references('coupon_id')->on('coupon')->onDelete('no action')->onUpdate('cascade');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('no action')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order');
    }
};
