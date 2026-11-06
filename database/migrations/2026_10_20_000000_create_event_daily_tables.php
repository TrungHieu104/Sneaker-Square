<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_daily_totals', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('event_type', 32);
            $table->unsignedInteger('events');
            $table->unsignedInteger('sessions');

            $table->index(['day', 'event_type']);
        });

        Schema::create('event_daily_variants', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('event_type', 32);
            $table->unsignedBigInteger('pro_id');
            $table->unsignedInteger('size_id')->nullable();
            $table->unsignedInteger('color_id')->nullable();
            $table->unsignedInteger('events');
            $table->unsignedInteger('quantity');

            $table->index(['day', 'event_type']);
            $table->index(['pro_id', 'size_id', 'color_id']);
        });

        Schema::create('search_daily', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('keyword', 191);
            $table->unsignedInteger('searches');
            $table->unsignedInteger('misses');

            $table->index(['day', 'keyword']);
        });

        Schema::create('recommendation_daily', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('source', 16);
            $table->unsignedInteger('shown');
            $table->unsignedInteger('clicks');
            $table->unsignedInteger('carts');
            $table->unsignedInteger('purchases');

            $table->index(['day', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_daily');
        Schema::dropIfExists('search_daily');
        Schema::dropIfExists('event_daily_variants');
        Schema::dropIfExists('event_daily_totals');
    }
};
