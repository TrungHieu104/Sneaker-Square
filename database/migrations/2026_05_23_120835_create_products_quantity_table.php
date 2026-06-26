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
        Schema::create('products_quantity', function (Blueprint $table) {
            $table->id('quantity_id');
            $table->unsignedBigInteger('quantity');
            $table->date('quantity_date', $precision = 0);
            $table->unsignedBigInteger('pro_id');
            $table->foreign('pro_id')->references('pro_id')->on('products')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedInteger('size_id')->nullable();
            $table->foreign('size_id')->references('size_id')->on('size')->onDelete('restrict')->onUpdate('cascade');
            $table->unsignedInteger('color_id')->nullable();
            $table->foreign('color_id')->references('color_id')->on('color')->onDelete('restrict')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products_quantity');
    }
};
