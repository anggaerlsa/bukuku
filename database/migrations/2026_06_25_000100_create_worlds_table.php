<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** A World (Dunia) is one universe owned by an author. */
    public function up(): void
    {
        Schema::create('worlds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('premise')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('status')->default('active'); // concept | active | archived
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worlds');
    }
};
