<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The visual skin a novel and all its sub-pages render in. Values are the
     * keys of App\Support\NovelTheme; unknown values fall back to "normal",
     * so this is safe even if a theme is ever retired.
     */
    public function up(): void
    {
        Schema::table('novels', function (Blueprint $table) {
            $table->string('theme', 20)->default('normal')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('novels', function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }
};
