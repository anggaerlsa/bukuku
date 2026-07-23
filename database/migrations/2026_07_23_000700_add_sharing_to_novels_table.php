<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An author can share a novel so every signed-in member may read its lore
     * — worlds, locations, characters — as reference. Read only: sharing
     * widens the `view` policy and nothing else, so editing stays with the
     * owner (and whoever can manage novels).
     *
     * This is NOT open to the internet; the whole app sits behind auth.
     */
    public function up(): void
    {
        Schema::table('novels', function (Blueprint $table) {
            $table->boolean('is_shared')->default(false)->after('status');
            $table->timestamp('shared_at')->nullable()->after('is_shared');

            $table->index('is_shared');
        });
    }

    public function down(): void
    {
        Schema::table('novels', function (Blueprint $table) {
            $table->dropIndex(['is_shared']);
            $table->dropColumn(['is_shared', 'shared_at']);
        });
    }
};
