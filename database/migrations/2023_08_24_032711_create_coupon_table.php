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
        Schema::create('coupon', function (Blueprint $table) {
            $table->id('coupon_id');
            $table->string('coupon_name', 255)->unique();
            $table->string('coupon_code', 100)->unique();
            $table->unsignedInteger('coupon_value');
            $table->unsignedInteger('coupon_quantity')->nullable();
            $table->unsignedInteger('coupon_used')->default(0);
            $table->boolean('coupon_condition');
            $table->timestamp('coupon_date')->useCurrent();
            $table->date('coupon_start', $precision = 0);
            $table->date('coupon_end', $precision = 0);
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon');
    }
};
