<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Comments on a book — where members who can read a shared novel talk back
     * to it. One level of replies: a comment may have a parent, but a reply
     * cannot itself be replied to, so `parent_id` always points at a top-level
     * comment and the thread never nests deeper.
     *
     * Both foreign keys cascade: a comment belongs to its book and its author,
     * and has no meaning once either is gone. A parent taken down carries its
     * replies with it — deleting the top of a thread deletes the thread.
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('body');
            // Set the first time a comment is edited. A fact, not an inference
            // from comparing timestamps — created_at and updated_at can land in
            // the same whole second and MySQL truncates the fraction, so a
            // quick edit would otherwise look untouched.
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();

            // The book page reads top-level comments oldest-first, then each
            // one's replies; this index serves both without a filesort.
            $table->index(['book_id', 'parent_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
