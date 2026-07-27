<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Comments were tied to a book. Make them polymorphic so a chapter can
     * carry them too — the reading page, where per-episode comments belong,
     * Wattpad-style — without a second parallel system.
     *
     * The table shipped empty (comments were added only hours earlier and had
     * no rows anywhere), so this rebuilds rather than performs the fiddly
     * drop-FK / drop-index / swap-column dance. The guard makes that safe: if
     * this ever meets real comments, it refuses rather than dropping them.
     */
    public function up(): void
    {
        if (Schema::hasTable('comments') && DB::table('comments')->exists()) {
            throw new RuntimeException(
                'Tabel comments tidak kosong — menolak membangun ulang agar komentar tak hilang. '
                . 'Pindahkan datanya ke bentuk polimorfik secara manual dulu.'
            );
        }

        Schema::dropIfExists('comments');

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            // The thing being commented on — a Book or a Chapter. Stored as the
            // model's class; the app's morph map is partial, so unmapped models
            // keep their FQCN, which is fine here.
            $table->string('commentable_type');
            $table->unsignedBigInteger('commentable_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();

            $table->index(['commentable_type', 'commentable_id', 'parent_id', 'id'], 'comments_thread_idx');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('comments') && DB::table('comments')->exists()) {
            throw new RuntimeException('Tabel comments tidak kosong — menolak membangun ulang.');
        }

        Schema::dropIfExists('comments');

        // Back to the book-only shape.
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();

            $table->index(['book_id', 'parent_id', 'id']);
        });
    }
};
