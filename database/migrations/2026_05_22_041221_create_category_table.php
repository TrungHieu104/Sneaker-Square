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
        Schema::create('category', function (Blueprint $table) {
            $table->increments('cate_id');
            $table->string('cate_name', 255)->unique();
            $table->string('cate_slug', 255)->unique()->nullable();
            $table->unsignedInteger('cate_sort')->unique();
            $table->string('cate_img', 255)->unique()->nullable();
            $table->boolean('cate_hidden')->default(1);
            $table->string('cate_meta_keywords', 2000)->nullable();
            $table->unsignedInteger('cate_parent_id')->nullable();
            $table->foreign('cate_parent_id')->references('cate_id')->on('category')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category');
    }
};
