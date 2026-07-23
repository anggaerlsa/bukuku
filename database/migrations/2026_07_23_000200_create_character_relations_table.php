<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A tie between two characters, stored ONCE. The reverse direction is
     * derived at read time from the inverse-type map on CharacterRelation, so
     * the two sides can never drift apart.
     *
     * Reading: "related_character is the {type} of character".
     */
    public function up(): void
    {
        Schema::create('character_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['character_id', 'related_character_id', 'type'], 'character_relations_pair_type_unique');
            $table->index(['related_character_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_relations');
    }
};
