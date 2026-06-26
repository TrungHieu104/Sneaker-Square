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
        Schema::create('promotion', function (Blueprint $table) {
            $table->increments('promotion_id');
            $table->string('type', 255);
            $table->string('promotion_name', 255);
            $table->string('promotion_img', 255);
            $table->string('promotion_link', 255);
            $table->boolean('promotion_hidden')->default(1);
            $table->unsignedInteger('promotion_sort');
            $table->string('promotion_content', 255);
            $table->timestamp('promotion_date', $precision = 0);
            $table->date('promotion_start', $precision = 0);
            $table->date('promotion_end', $precision = 0);
            $table->string('promotion_note', 2000)->nullable();
            $table->unsignedInteger('cate_slide_id')->nullable();
            $table->foreign('cate_slide_id')->references('cate_slide_id')->on('cate_slide')->onDelete('restrict')->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion');
    }
};
