<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon', function (Blueprint $table) {
            $table->id('coupon_id');
            $table->string('coupon_name', 255)->unique();
            $table->string('coupon_code', 100)->unique();
            $table->unsignedInteger('coupon_value');
            $table->unsignedInteger('coupon_min_order')->default(0);
            $table->unsignedInteger('coupon_max_discount')->nullable();
            $table->unsignedInteger('coupon_quantity')->nullable();
            $table->unsignedInteger('coupon_used')->default(0);
            $table->boolean('coupon_condition');
            $table->timestamp('coupon_date')->useCurrent();
            $table->date('coupon_start');
            $table->date('coupon_end');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon');
    }
};
