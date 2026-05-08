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
        Schema::create('cate_news', function (Blueprint $table) {
            $table->increments('cate_news_id');
            $table->string('cate_news_name', 255)->unique();
            $table->string('cate_news_slug', 255)->unique()->nullable();
            $table->unsignedInteger('cate_news_sort');
            $table->string('cate_news_img', 255)->unique()->nullable();
            $table->boolean('cate_news_hidden')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cate_news');
    }
};
