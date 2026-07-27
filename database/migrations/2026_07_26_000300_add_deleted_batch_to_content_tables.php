<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marks which records went into the bin as part of the SAME deletion.
     *
     * The first attempt matched on `deleted_at` alone, and it was wrong for the
     * same reason the comment "edited" flag was: timestamps are stored to the
     * second. A character thrown away moments before its world was deleted got
     * an identical stamp, so restoring the world resurrected it too — quietly
     * undoing a deliberate deletion.
     *
     * A batch id records the fact instead of inferring it. Restore brings back
     * exactly the records that went down with this parent, and nothing else.
     *
     * Additive on purpose: the tables already hold the author's real work, so
     * this adds a nullable column rather than rebuilding anything.
     */
    private const TABLES = [
        'novels', 'worlds', 'books', 'chapters', 'characters',
        'organizations', 'lore_entries',
        'benuas', 'negaras', 'provinsis', 'kotas', 'desas',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasColumn($table, 'deleted_batch')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->uuid('deleted_batch')->nullable()->after('deleted_at')->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasColumn($table, 'deleted_batch')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropIndex(['deleted_batch']);
                    $t->dropColumn('deleted_batch');
                });
            }
        }
    }
};
