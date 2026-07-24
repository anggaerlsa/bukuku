<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Self-registration is open again, but a new account starts as `pending`
     * and can only read a waiting page until a superadmin approves it.
     *
     * The column defaults to `pending` so every future signup lands there;
     * the backfill right below runs once, immediately after the column is
     * created, so it can only ever touch accounts that already existed —
     * those were invited by an admin and are legitimately active.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('password');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->after('approved_at')
                ->constrained('users')->nullOnDelete();

            $table->index('status');
        });

        DB::table('users')->update(['status' => 'active', 'approved_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'approved_at', 'approved_by']);
        });
    }
};
