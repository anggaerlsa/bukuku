<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One table per location tier: benuas → negaras → provinsis → kotas → desas.
     * Each child points at its parent tier's table; every row also carries
     * world_id so a world's locations can be queried directly.
     */
    public function up(): void
    {
        // Lore fields shared by every tier.
        $lore = function (Blueprint $table) {
            $table->string('name');
            $table->string('type')->nullable();  // free-text in-world label (Dukedom, Metropolis…)
            $table->string('summary')->nullable();
            $table->text('description')->nullable();
            $table->text('geography')->nullable();
            $table->string('climate')->nullable();
            $table->string('population')->nullable();
            $table->string('government')->nullable();
            $table->text('points_of_interest')->nullable();
            $table->string('map_image')->nullable();
            $table->timestamps();
        };

        Schema::create('benuas', function (Blueprint $table) use ($lore) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $lore($table);
        });

        Schema::create('negaras', function (Blueprint $table) use ($lore) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->foreignId('benua_id')->constrained('benuas')->cascadeOnDelete();
            $lore($table);
        });

        Schema::create('provinsis', function (Blueprint $table) use ($lore) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->foreignId('negara_id')->constrained('negaras')->cascadeOnDelete();
            $lore($table);
        });

        Schema::create('kotas', function (Blueprint $table) use ($lore) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provinsi_id')->constrained('provinsis')->cascadeOnDelete();
            $lore($table);
        });

        Schema::create('desas', function (Blueprint $table) use ($lore) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kota_id')->constrained('kotas')->cascadeOnDelete();
            $lore($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desas');
        Schema::dropIfExists('kotas');
        Schema::dropIfExists('provinsis');
        Schema::dropIfExists('negaras');
        Schema::dropIfExists('benuas');
    }
};
