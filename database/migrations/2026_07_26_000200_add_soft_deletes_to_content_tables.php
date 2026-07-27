<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A safety net for the author's work: nothing they wrote should vanish on
     * one mis-click. Every table that holds something an author would mourn
     * gets `deleted_at`, so a delete becomes recoverable from the Sampah page
     * and only an explicit "hapus permanen" is final.
     *
     * Join rows and attachments are deliberately left out — memberships,
     * relations, custom-field values, gallery rows, comments. They have no
     * life of their own, and they ride along with whatever owns them.
     *
     * NOTE for whoever extends this: a soft delete is an UPDATE, so the FK
     * cascades on these tables do NOT fire. Descendants are soft-deleted in
     * PHP (see Concerns\CascadesSoftDeletes); the DB cascade only ever runs on
     * a permanent delete, which is exactly when we do want it.
     */
    private const TABLES = [
        'novels',
        'worlds',
        'books',
        'chapters',
        'characters',
        'organizations',
        'lore_entries',
        'benuas',
        'negaras',
        'provinsis',
        'kotas',
        'desas',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->softDeletes();
                    // Every listing filters on this, and the Sampah page sorts
                    // by it, so it earns an index.
                    $t->index('deleted_at');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropIndex(['deleted_at']);
                    $t->dropSoftDeletes();
                });
            }
        }
    }
};
