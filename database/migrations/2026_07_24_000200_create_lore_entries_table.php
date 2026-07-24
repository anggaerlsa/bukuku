<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Free-form lore articles — everything that is neither a person, a place
     * nor a faction: magic systems, pantheons, glossaries, technologies,
     * doctrines. Four files of the author's own story guide had no home in
     * the app before this and had to be skipped during the import.
     *
     * `category` is deliberately free text, not an enum: the vocabulary is
     * the author's and differs per genre. See App\Support\LoreCategories.
     */
    public function up(): void
    {
        Schema::create('lore_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->string('category')->nullable();
            $table->string('title');
            $table->string('summary')->nullable();
            $table->text('body')->nullable();
            $table->string('cover_image')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['world_id', 'category', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lore_entries');
    }
};
