<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->increments('page_id');
            $table->string('page_type', 20)->default('policy');
            $table->string('page_name', 255)->unique();
            $table->text('page_description');
            $table->text('page_content');
            $table->string('page_slug', 255)->unique()->nullable();
            $table->boolean('page_hidden')->default(1);
            $table->string('page_meta_keywords', 2000)->nullable();
            $table->string('page_meta_description', 2000)->nullable();
            $table->string('page_created_by', 255)->nullable();
            $table->string('page_updated_by', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
