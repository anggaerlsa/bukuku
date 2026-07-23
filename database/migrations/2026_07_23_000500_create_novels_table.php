<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A novel sits one level above worlds: the book being written, which may
     * span several settings (a space-pirate story can visit many planets).
     * One author → many novels → many worlds → the lore under each world.
     *
     * `worlds.novel_id` is required — every world belongs to a novel. The app
     * additionally refuses to delete a novel that still has worlds, so the
     * cascade below is a backstop, not the normal path.
     */
    public function up(): void
    {
        Schema::create('novels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('synopsis')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('status', 20)->default('concept');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::table('worlds', function (Blueprint $table) {
            $table->foreignId('novel_id')->after('user_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('worlds', function (Blueprint $table) {
            $table->dropForeign(['novel_id']);
            $table->dropColumn('novel_id');
        });

        Schema::dropIfExists('novels');
    }
};
