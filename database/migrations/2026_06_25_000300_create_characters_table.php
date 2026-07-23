<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Characters belong to a world. */
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('aliases')->nullable();
            $table->string('role')->nullable();        // protagonis, antagonis, …
            $table->string('species')->nullable();
            $table->string('gender')->nullable();
            $table->string('age')->nullable();
            $table->string('status')->nullable();      // hidup, wafat, …
            $table->string('occupation')->nullable();
            $table->string('affiliation')->nullable();
            $table->text('appearance')->nullable();
            $table->text('personality')->nullable();
            $table->text('backstory')->nullable();
            $table->text('goals')->nullable();
            $table->string('portrait_image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
