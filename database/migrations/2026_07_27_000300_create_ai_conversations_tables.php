<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chat history, locked to one novel.
     *
     * `novel_id` is required, not nullable: a conversation that belonged to no
     * novel in particular would be the one place the assistant's memory could
     * carry material from one novel into another. There is deliberately no way
     * to express that row.
     *
     * `user_id` sits alongside it even though only the novel's owner may chat.
     * Ownership of a novel can be reassigned; the transcript should stay with
     * the person who wrote it rather than follow the novel to someone new.
     *
     * Soft-deletable with a batch column so a binned novel takes its
     * conversations along and a restore brings back exactly those — the same
     * contract every other child of a novel already follows.
     *
     * Messages are NOT soft-deletable: they have no life of their own and no
     * uploaded files to strand, so the foreign key's cascade is enough.
     */
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('novel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('provider', 30)->default('deepseek');
            $table->string('model', 60)->nullable();
            // Whether the manuscript was part of the context, recorded per
            // conversation: the answers only make sense read against what the
            // assistant could actually see.
            $table->boolean('with_manuscript')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('deleted_batch')->nullable()->index();

            $table->index(['novel_id', 'user_id']);
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->longText('content');
            // Usage as the provider reported it, for the author's own accounting.
            $table->unsignedInteger('tokens_in')->nullable();
            $table->unsignedInteger('tokens_out')->nullable();
            $table->unsignedInteger('tokens_cached')->nullable();
            $table->timestamps();

            $table->index(['ai_conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
