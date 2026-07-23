<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gallery images for any lore record — a character or a location in any of
     * the five tier tables. `imageable_type` holds the short alias from the
     * morph map ("character", "kota", …), same convention as the character's
     * origin/residence link.
     *
     * The existing single `portrait_image` / `map_image` column stays as the
     * record's cover; this table holds everything beyond that one image.
     */
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->morphs('imageable');
            $table->string('path');          // storage path, or an external http(s) URL
            $table->string('caption')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['imageable_type', 'imageable_id', 'position'], 'images_owner_position_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
