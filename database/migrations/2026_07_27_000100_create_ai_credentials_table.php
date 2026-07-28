<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every author brings their own API key, so the app never carries a bill or
     * a shared secret for the whole installation.
     *
     * The key has to be usable, which means it is encrypted rather than hashed —
     * recoverable by design. Two things follow from that and are handled here:
     *
     * - `key_tail` holds the last few characters in the clear. The settings page
     *   shows "sk-…a1b2" from this column alone, so displaying a saved key never
     *   decrypts anything. The only place the real key is decrypted is the
     *   moment a request goes out to the provider.
     * - `verified_at` records that the key actually answered once. A typo
     *   otherwise surfaces much later, as a failed chat with no clue why.
     *
     * One row per user per provider: switching model or rotating the key is an
     * update, not a second row that could quietly shadow the first.
     */
    public function up(): void
    {
        Schema::create('ai_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30)->default('deepseek');
            // Ciphertext, not the key. Laravel's `encrypted` cast writes here.
            $table->text('api_key');
            $table->string('key_tail', 8)->nullable();
            $table->string('model', 60)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_credentials');
    }
};
