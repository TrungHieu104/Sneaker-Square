<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->id('topup_id');
            $table->unsignedBigInteger('wallet_id');
            $table->foreign('wallet_id')->references('wallet_id')->on('wallets')->onDelete('cascade')->onUpdate('cascade');
            $table->string('topup_code', 50)->unique();
            $table->unsignedInteger('amount');
            $table->string('gateway', 20);
            $table->string('status', 20)->default('pending')->index();
            $table->string('gateway_reference', 100)->nullable();
            $table->longText('checkout_url')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_topups');
    }
};
