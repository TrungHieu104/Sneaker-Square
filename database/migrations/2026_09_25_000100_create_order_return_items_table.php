<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_return_items', function (Blueprint $table) {
            $table->id('return_item_id');
            $table->unsignedBigInteger('return_id');
            $table->foreign('return_id')->references('return_id')->on('order_returns')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('order_details_id');
            $table->foreign('order_details_id')->references('order_details_id')->on('order_details')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedInteger('quantity');
            $table->unique(['return_id', 'order_details_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_return_items');
    }
};
