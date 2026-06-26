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
        Schema::create('products', function (Blueprint $table) {
            $table->id('pro_id');
            $table->string('pro_name', 255)->unique();
            $table->string('pro_slug', 255)->unique()->nullable();
            $table->string('pro_code', 50)->unique();
            $table->unsignedInteger('pro_price');
            $table->unsignedInteger('pro_price_sale')->default(0);
            $table->unsignedInteger('capital_price');
            $table->string('pro_img', 255)->unique();
            $table->text('pro_description')->nullable();
            $table->string('pro_SEO_title', 255)->nullable();
            $table->unsignedBigInteger('pro_views')->default(0);
            $table->boolean('pro_hot')->default(0);
            $table->boolean('pro_hidden')->default(1);
            $table->string('pro_meta_keywords', 2000)->nullable();
            $table->string('pro_meta_description', 2000)->nullable();
            $table->date('pro_date', $precision = 0);
            $table->unsignedInteger('cate_id');
            $table->foreign('cate_id')->references('cate_id')->on('category')->onDelete('restrict')->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
