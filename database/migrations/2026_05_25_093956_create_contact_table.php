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
        Schema::create('contact', function (Blueprint $table) {
            $table->increments('contact_id');
            $table->string('contact_email', 255);
            $table->string('contact_phone', 15);
            $table->string('contact_address', 255)->unique();
            $table->string('map_link', 2000)->nullable();
            $table->string('fanpage_link', 2000)->nullable();
            $table->string('tawk_link', 2000)->nullable();
            $table->boolean('contact_hidden')->default(1);
            $table->string('contact_created_by', 255)->nullable();
            $table->string('contact_updated_by', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact');
    }
};
