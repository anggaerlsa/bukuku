<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Author-defined attributes, scoped to a single world so each world can
     * carry whatever its genre needs ("Tingkat Mana", "Klearans Keamanan").
     *
     * `applies_to` is either "character", "location" (every tier) or a single
     * tier key. Values are attached polymorphically, using the same short
     * morph aliases as the rest of the app.
     */
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('applies_to', 30);
            $table->string('type', 20)->default('text');
            $table->text('options')->nullable();   // one choice per line, for type=select
            $table->string('hint')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['world_id', 'applies_to', 'position']);
        });

        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_field_id')->constrained()->cascadeOnDelete();
            $table->morphs('valuable');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['custom_field_id', 'valuable_type', 'valuable_id'], 'custom_field_values_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_fields');
    }
};
