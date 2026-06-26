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
        Schema::create('faq', function (Blueprint $table) {
            $table->increments('faq_id');
            $table->string('faq_name', 255)->unique();
            $table->text('faq_description');
            $table->text('faq_content');
            $table->string('faq_slug', 255)->unique()->nullable();
            $table->boolean('faq_hidden')->default(1);
            $table->boolean('faq_about')->default(0);
            $table->string('faq_meta_keywords', 2000)->nullable();
            $table->string('faq_meta_description', 2000)->nullable();
            $table->string('faq_created_by', 255)->nullable();
            $table->string('faq_updated_by', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faq');
    }
};
