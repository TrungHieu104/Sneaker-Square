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
        Schema::create('order_details', function (Blueprint $table) {
            $table->id('order_details_id');
            $table->string('pro_name', 255);
            $table->string('size',5)->nullable()->default(null);
            $table->string('color',20)->nullable()->default(null);
            $table->unsignedInteger('price');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('order_id');
            $table->foreign('order_id')->references('order_id')->on('order')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('pro_id');
            $table->foreign('pro_id')->references('pro_id')->on('products')->onDelete('no action')->onUpdate('cascade');
            // $table->primary(['order_id', 'pro_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
