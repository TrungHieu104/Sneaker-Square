<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_withdrawals', function (Blueprint $table) {
            $table->id('withdrawal_id');
            $table->unsignedBigInteger('wallet_id');
            $table->foreign('wallet_id')->references('wallet_id')->on('wallets')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedInteger('amount');
            $table->string('bank_name', 100);
            $table->string('bank_account', 50);
            $table->string('account_holder', 100);
            $table->string('status', 20)->default('requested')->index();
            $table->string('note', 255)->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_withdrawals');
    }
};
