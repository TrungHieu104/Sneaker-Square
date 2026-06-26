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
        Schema::create('news', function (Blueprint $table) {
            $table->id('news_id');
            $table->string('news_title', 255)->unique();
            $table->string('news_slug', 255)->unique()->nullable();
            $table->text('news_summarize');
            $table->text('news_content');
            $table->boolean('news_hidden')->default(1);
            $table->string('news_img', 255)->unique()->nullable();
            $table->string('news_SEO_title', 255)->nullable();
            $table->string('news_meta_keywords', 2000)->nullable();
            $table->string('news_meta_description', 2000)->nullable();
            $table->unsignedBigInteger('views')->default(0);
            $table->boolean('news_hot')->default(0);
            $table->date('post_date', $precision = 0);
            $table->string('news_created_by', 255)->nullable();
            $table->unsignedInteger('cate_news_id');
            $table->foreign('cate_news_id')->references('cate_news_id')->on('cate_news')->onDelete('restrict')->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
