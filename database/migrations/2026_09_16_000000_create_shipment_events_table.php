<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_events', function (Blueprint $table) {
            $table->id('event_id');
            $table->unsignedBigInteger('order_id');
            $table->foreign('order_id')->references('order_id')->on('order')->onDelete('cascade')->onUpdate('cascade');
            $table->string('carrier', 20)->default('ghn');
            $table->string('shipping_code', 50)->nullable();
            $table->string('status', 50);
            $table->string('description', 500)->nullable();
            $table->string('warehouse', 255)->nullable();
            $table->dateTime('happened_at');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'shipping_code', 'status', 'happened_at'], 'shipment_events_parcel_unique');
            $table->index(['order_id', 'happened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
    }
};
