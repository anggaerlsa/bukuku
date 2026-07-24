<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Organisations (wangsa, pasukan, sekte, guild) — the third leg of the
     * foundation next to places and people.
     *
     * Before this, membership lived in `characters.affiliation` as free text,
     * which had already started to break down: one row held two affiliations
     * joined by a "·", and ranks like "Laksamana Tertinggi" were stuffed into
     * `occupation`. A membership row carries the rank instead.
     *
     * `parent_id` lets a division sit under its army; `headquarters_*` reuses
     * the same `tier:id` polymorphic location link the characters use.
     */
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('name');
            $table->string('aliases')->nullable();
            $table->string('type')->nullable();      // free-text in-world label: Wangsa, Divisi, Sekte…
            $table->string('status', 20)->default('aktif');
            $table->string('motto')->nullable();
            $table->string('summary')->nullable();
            $table->text('description')->nullable();
            $table->text('purpose')->nullable();     // tujuan / doktrin
            $table->text('history')->nullable();
            $table->string('emblem_image')->nullable();
            $table->nullableMorphs('headquarters');  // markas → a location in any tier
            $table->timestamps();

            $table->index(['world_id', 'name']);
        });

        Schema::create('organization_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();      // jabatan/pangkat di dalam organisasi
            $table->string('status', 20)->default('aktif');  // aktif | mantan
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'character_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_members');
        Schema::dropIfExists('organizations');
    }
};
