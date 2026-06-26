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
        Schema::create('delivery_info', function (Blueprint $table) {
            $table->increments('info_id');
            $table->string('info_name', 255);
            $table->string('info_phone', 15);
            $table->string('info_email', 255);
            $table->string('info_address', 255);
            $table->string('info_ward', 255);
            $table->string('info_district', 255);
            $table->string('info_province', 255);
            $table->string('info_delivery_fee', 255);
            $table->boolean('info_default')->default(0);
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('address');
    }
};
