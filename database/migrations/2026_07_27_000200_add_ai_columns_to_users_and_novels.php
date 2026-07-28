<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The two gates in front of the AI assistant. Both default to "off", and
     * both have to be opened by the author by hand.
     *
     * `users.ai_consented_at` — the disclosure. Talking to the assistant sends
     * an entire novel, unpublished manuscript included, to a third party's
     * servers. That is not a detail to bury in a tooltip, so nothing works
     * until the author has read the notice and said yes. The version number
     * next to it means the notice can be reworded later and asked again,
     * instead of a past yes silently covering terms that have changed.
     *
     * `novels.ai_enabled` — the per-novel arming switch. Consent says the
     * author accepts the trade; this says which novels it applies to. A novel
     * left alone is never assembled into a prompt at all.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('ai_consented_at')->nullable()->after('approved_by');
            $table->unsignedSmallInteger('ai_consent_version')->nullable()->after('ai_consented_at');
        });

        Schema::table('novels', function (Blueprint $table) {
            $table->boolean('ai_enabled')->default(false)->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ai_consented_at', 'ai_consent_version']);
        });

        Schema::table('novels', function (Blueprint $table) {
            $table->dropColumn('ai_enabled');
        });
    }
};
