<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The manuscript itself, which the app had nowhere to keep: a novel holds
     * one or more Buku (volumes), and each Buku holds its Bab (chapters) in
     * reading order.
     *
     * This is the written work, NOT the plot outline — Babak → Bab → Adegan as
     * a planning structure stays deferred. What lands here is text an author
     * has actually written.
     *
     * `body` is longText on purpose: a chapter of a few thousand words already
     * passes what `text` holds comfortably once a long one comes along.
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('novel_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('synopsis')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['novel_id', 'position']);
        });

        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('body')->nullable();

            // Reading order within the book. Kept separate from any number in
            // the title: an archive's file order and its chapter numbering
            // drift apart the moment a part is split or renamed.
            $table->unsignedInteger('position')->default(0);

            // Cached so a table of contents does not have to count words
            // across every chapter body on each render.
            $table->unsignedInteger('word_count')->default(0);

            // When the author first published it, where that is known — an
            // imported chapter carries its original date, not today's.
            $table->timestamp('published_at')->nullable();

            // Where an imported chapter came from, so the import stays
            // lossless and a reader can reach the original.
            $table->string('source_url')->nullable();

            $table->timestamps();

            $table->index(['book_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapters');
        Schema::dropIfExists('books');
    }
};
