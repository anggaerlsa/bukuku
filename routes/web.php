<?php

use App\Http\Controllers\CharacterController;
use App\Http\Controllers\CharacterRelationController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LoreEntryController;
use App\Http\Controllers\NovelController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationMemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorldController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| The Scriptorium — authenticated builder's workspace
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('kelola')->group(function () {
        // Novels — the layer above worlds: the book, which may span several
        // settings. One author → many novels → many worlds.
        Route::resource('novel', NovelController::class)
            ->parameters(['novel' => 'novel'])
            ->names('novels');

        // Share a novel read-only with every signed-in member.
        Route::patch('novel/{novel}/bagikan', [NovelController::class, 'share'])
            ->name('novels.share');

        // Worlds (Dunia) — each a setting belonging to one novel.
        Route::resource('dunia', WorldController::class)
            ->parameters(['dunia' => 'world'])
            ->names('worlds');

        // Characters (Karakter) — nested lore, scoped to their world.
        Route::resource('dunia.karakter', CharacterController::class)
            ->parameters(['dunia' => 'world', 'karakter' => 'character'])
            ->names('characters')
            ->scoped();

        // Character ↔ character ties. Stored once; both characters' pages show
        // them, so either side may add or remove one.
        Route::scopeBindings()->group(function () {
            Route::post('dunia/{world}/karakter/{character}/relasi', [CharacterRelationController::class, 'store'])
                ->name('character-relations.store');
            Route::delete('dunia/{world}/karakter/{character}/relasi/{relation}', [CharacterRelationController::class, 'destroy'])
                ->name('character-relations.destroy')
                ->whereNumber('relation');
        });

        // Locations (Lokasi) — one table per tier (benua→negara→provinsi→kota→desa),
        // served by one unified controller. show/edit/update/destroy carry {tier}.
        Route::prefix('dunia/{world}/lokasi')->group(function () {
            $tiers = \App\Support\Hierarchy::keys();
            Route::get('/', [LocationController::class, 'index'])->name('locations.index');
            Route::get('/create', [LocationController::class, 'create'])->name('locations.create');
            Route::post('/', [LocationController::class, 'store'])->name('locations.store');
            Route::get('/{tier}/{id}', [LocationController::class, 'show'])->name('locations.show')->whereIn('tier', $tiers)->whereNumber('id');
            Route::get('/{tier}/{id}/edit', [LocationController::class, 'edit'])->name('locations.edit')->whereIn('tier', $tiers)->whereNumber('id');
            Route::put('/{tier}/{id}', [LocationController::class, 'update'])->name('locations.update')->whereIn('tier', $tiers)->whereNumber('id');
            Route::delete('/{tier}/{id}', [LocationController::class, 'destroy'])->name('locations.destroy')->whereIn('tier', $tiers)->whereNumber('id');
        });

        // Lore articles — everything that is neither person, place nor faction.
        // Param dinamai loreEntry, bukan lore: scoped binding mencari relasi
        // dari bentuk jamak nama parameter, dan World punya loreEntries().
        Route::resource('dunia.lore', LoreEntryController::class)
            ->parameters(['dunia' => 'world', 'lore' => 'loreEntry'])
            ->names('lore')
            ->scoped();

        // Organisations (Organisasi) — factions, houses, armies, orders.
        Route::resource('dunia.organisasi', OrganizationController::class)
            ->parameters(['dunia' => 'world', 'organisasi' => 'organization'])
            ->names('organizations')
            ->scoped();

        // Membership ties a character to an organisation, with their rank.
        Route::scopeBindings()->group(function () {
            Route::post('dunia/{world}/keanggotaan', [OrganizationMemberController::class, 'store'])
                ->name('organization-members.store');
            Route::delete('dunia/{world}/keanggotaan/{member}', [OrganizationMemberController::class, 'destroy'])
                ->name('organization-members.destroy')
                ->whereNumber('member');
        });

        // Custom fields (Atribut) — attributes the author invents per world.
        Route::resource('dunia.atribut', CustomFieldController::class)
            ->parameters(['dunia' => 'world', 'atribut' => 'customField'])
            ->names('custom-fields')
            ->except(['show'])
            ->scoped();

        // Galleries — many images per character or location, one controller for
        // both. {type} is the morph alias: "character" or a location tier key.
        Route::prefix('dunia/{world}/galeri')->group(function () {
            $types = array_keys(\App\Support\ImageOwners::types());
            Route::post('/{type}/{id}', [ImageController::class, 'store'])
                ->name('images.store')->whereIn('type', $types)->whereNumber('id');
            Route::delete('/{image}', [ImageController::class, 'destroy'])
                ->name('images.destroy')->whereNumber('image');
            Route::patch('/{image}/sampul', [ImageController::class, 'cover'])
                ->name('images.cover')->whereNumber('image');
        });

        // Genre tags (admin).
        Route::resource('genres', GenreController::class)
            ->except(['show'])
            ->middleware('permission:manage genres');

        // Stewards — user & role administration (admin).
        Route::resource('users', UserController::class)
            ->except(['show'])
            ->middleware('permission:manage users');
    });
});

require __DIR__ . '/auth.php';
