<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->char('session_id', 40)->nullable();
            $table->string('status', 16)->default('bot');
            $table->unsignedBigInteger('pro_id')->nullable();
            $table->foreign('pro_id')->references('pro_id')->on('products')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('handed_off_at')->nullable();
            $table->timestamp('customer_seen_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_message_at']);
            $table->index('session_id');
        });

        Schema::create('assistant_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('assistant_conversations')->cascadeOnDelete();
            $table->string('sender', 8);
            $table->text('body');
            $table->string('intent', 16)->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->foreign('staff_id')->references('user_id')->on('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('created_at')->nullable();

            $table->index(['conversation_id', 'id']);
            $table->index(['sender', 'created_at']);
        });

        Schema::create('assistant_knowledge', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type', 16)->default('all');
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('title');
            $table->text('content');
            $table->foreignId('message_id')->nullable()->unique()->constrained('assistant_messages')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('user_id')->on('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['scope_type', 'scope_id']);
        });

        Schema::create('assistant_unanswered', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('assistant_conversations')->cascadeOnDelete();
            $table->foreignId('message_id')->constrained('assistant_messages')->cascadeOnDelete();
            $table->string('question', 500);
            $table->string('intent', 16);
            $table->string('reason', 16)->default('no_data');
            $table->string('topic', 24)->nullable();
            $table->unsignedBigInteger('pro_id')->nullable();
            $table->foreign('pro_id')->references('pro_id')->on('products')->nullOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->string('resolution', 16)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('knowledge_id')->nullable()->constrained('assistant_knowledge')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['resolved_at', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_unanswered');
        Schema::dropIfExists('assistant_knowledge');
        Schema::dropIfExists('assistant_messages');
        Schema::dropIfExists('assistant_conversations');
    }
};
