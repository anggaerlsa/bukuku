<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A character's origin (asal) and residence (domisili) point at a real
     * location. Locations live in five separate tables, so the link is
     * polymorphic: *_type holds the tier key (benua/negara/…, via the morph
     * map in AppServiceProvider) and *_id the row id in that tier's table.
     */
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->nullableMorphs('origin');
            $table->nullableMorphs('residence');
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropMorphs('origin');
            $table->dropMorphs('residence');
        });
    }
};
