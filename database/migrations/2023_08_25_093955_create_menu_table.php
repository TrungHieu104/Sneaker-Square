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
        Schema::create('menu', function (Blueprint $table) {
            $table->increments('menu_id');
            $table->string('menu_name', 255)->nullable();
            $table->string('menu_link', 255)->nullable();
            $table->boolean('menu_hidden')->default(1);
            $table->unsignedInteger('menu_position')->nullable();
            $table->unsignedInteger('menu_parent_id')->nullable();
            $table->foreign('menu_parent_id')->references('menu_id')->on('menu')->onDelete('restrict')->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu');
    }
};
