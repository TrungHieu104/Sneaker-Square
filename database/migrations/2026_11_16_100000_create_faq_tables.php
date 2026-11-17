<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_groups', function (Blueprint $table) {
            $table->increments('group_id');
            $table->string('group_name', 100)->unique();
            $table->unsignedSmallInteger('group_position')->default(0);
            $table->boolean('group_hidden')->default(1);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->increments('faq_id');
            $table->unsignedInteger('group_id');
            $table->string('faq_question', 255);
            $table->text('faq_answer');
            $table->unsignedSmallInteger('faq_position')->default(0);
            $table->boolean('faq_hidden')->default(1);
            $table->string('faq_created_by', 255)->nullable();
            $table->string('faq_updated_by', 255)->nullable();
            $table->timestamps();

            $table->foreign('group_id')->references('group_id')->on('faq_groups')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('faq_groups');
    }
};
