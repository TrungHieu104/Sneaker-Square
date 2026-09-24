<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->unsignedBigInteger('wallet_id');
            $table->foreign('wallet_id')->references('wallet_id')->on('wallets')->onDelete('cascade')->onUpdate('cascade');
            $table->string('type', 30);
            $table->string('direction', 3);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('balance_after');
            $table->string('reference_type', 30)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->string('entry_hash', 64)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['wallet_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
