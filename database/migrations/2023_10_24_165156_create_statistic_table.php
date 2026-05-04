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
        Schema::create('statistical', function (Blueprint $table) {
            $table->increments('id_statistical');
            $table->date('order_date');
            $table->unsignedInteger('sales');
            $table->unsignedInteger('profit');
            $table->unsignedInteger('order_total')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistical');
    }
};
