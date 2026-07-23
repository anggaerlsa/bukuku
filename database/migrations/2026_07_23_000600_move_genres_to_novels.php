<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Genre belongs to the novel, not the world: "a sci-fi story about space
     * pirates" describes the book, while its worlds are just the settings that
     * book visits.
     *
     * Both `genres` and `genre_world` were empty when this ran, so there was
     * nothing to carry over — the old pivot is simply replaced.
     */
    public function up(): void
    {
        Schema::create('genre_novel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('novel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['novel_id', 'genre_id']);
        });

        Schema::dropIfExists('genre_world');
    }

    public function down(): void
    {
        Schema::create('genre_world', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['world_id', 'genre_id']);
        });

        Schema::dropIfExists('genre_novel');
    }
};
