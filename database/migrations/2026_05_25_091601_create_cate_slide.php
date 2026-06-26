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
        Schema::create('cate_slide', function (Blueprint $table) {
            $table->increments('cate_slide_id');
            $table->string('cate_slide_name', 255);
            $table->string('cate_slide_slug', 255)->unique()->nullable();
            $table->boolean('cate_slide_hidden')->default(1);
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cate_slide');
    }
};
