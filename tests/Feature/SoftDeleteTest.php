<?php

namespace Tests\Feature;

use App\Models\Benua;
use App\Models\Book;
use App\Models\Character;
use App\Models\LoreEntry;
use App\Models\Novel;
use App\Models\Organization;
use App\Models\User;
use App\Models\World;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The safety net: nothing an author wrote may vanish on one mis-click.
 *
 * The rules worth pinning are the ones that are easy to get subtly wrong —
 * a soft delete is an UPDATE, so no FK cascade fires and descendants have to
 * be carried down by hand; restore must bring back exactly what went down
 * with this deletion and nothing that was already in the bin; and files,
 * comments and character links must survive until a PERMANENT delete.
 */
class SoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function author(): User
    {
        return tap(User::factory()->create(), fn (User $u) => $u->syncRoles(['author']));
    }

    private function novelFor(User $user): Novel
    {
        $n = ++$this->seq;

        return Novel::create([
            'user_id' => $user->id,
            'title' => "Novel {$n}",
            'slug' => "novel-{$n}",
            'status' => 'active',
        ]);
    }

    private function worldFor(Novel $novel, User $user): World
    {
        $n = ++$this->seq;

        return World::create([
            'user_id' => $user->id,
            'novel_id' => $novel->id,
            'name' => "Dunia {$n}",
            'slug' => "dunia-{$n}",
            'status' => 'active',
        ]);
    }

    public function test_deleting_a_world_bins_its_whole_lore(): void
    {
        $owner = $this->author();
        $world = $this->worldFor($this->novelFor($owner), $owner);

        $character = $world->characters()->create(['name' => 'Tokoh']);
        $org = $world->organizations()->create(['name' => 'Faksi', 'status' => 'aktif']);
        $lore = $world->loreEntries()->create(['title' => 'Sihir']);
        $benua = Benua::create(['world_id' => $world->id, 'name' => 'Benua Utama']);

        $world->delete();

        // Gone from sight...
        $this->assertSame(0, World::count());
        $this->assertSame(0, Character::count());
        $this->assertSame(0, Organization::count());
        $this->assertSame(0, LoreEntry::count());
        $this->assertSame(0, Benua::count());

        // ...but still there, recoverable.
        $this->assertSame(1, Character::onlyTrashed()->count());
        $this->assertSame(1, Benua::onlyTrashed()->count());
        $this->assertNotNull($character->fresh()?->deleted_at ?? Character::withTrashed()->find($character->id)->deleted_at);
        $this->assertNotNull(LoreEntry::withTrashed()->find($lore->id)->deleted_at);
        $this->assertNotNull(Organization::withTrashed()->find($org->id)->deleted_at);
    }

    public function test_restoring_a_world_brings_its_lore_back(): void
    {
        $owner = $this->author();
        $world = $this->worldFor($this->novelFor($owner), $owner);
        $world->characters()->create(['name' => 'Tokoh']);
        $world->loreEntries()->create(['title' => 'Sihir']);

        $world->delete();
        World::onlyTrashed()->find($world->id)->restore();

        $this->assertSame(1, World::count());
        $this->assertSame(1, Character::count());
        $this->assertSame(1, LoreEntry::count());
    }

    public function test_restore_leaves_things_that_were_already_in_the_bin(): void
    {
        $owner = $this->author();
        $world = $this->worldFor($this->novelFor($owner), $owner);
        $kept = $world->characters()->create(['name' => 'Ikut']);
        $earlier = $world->characters()->create(['name' => 'Sudah dibuang duluan']);

        // Thrown away on its own, before the world went.
        $earlier->delete();

        $world->delete();
        World::onlyTrashed()->find($world->id)->restore();

        // The one that went down WITH the world is back; the one the author
        // had already binned stays binned.
        $this->assertSame(1, Character::count());
        $this->assertSame('Ikut', Character::sole()->name);
        $this->assertNotNull(Character::withTrashed()->find($earlier->id)->deleted_at);
        $this->assertNull(Character::withTrashed()->find($kept->id)->deleted_at);
    }

    public function test_deleting_a_novel_bins_worlds_books_and_chapters(): void
    {
        $owner = $this->author();
        $novel = $this->novelFor($owner);
        $world = $this->worldFor($novel, $owner);
        $world->characters()->create(['name' => 'Tokoh']);

        $book = Book::create([
            'novel_id' => $novel->id, 'title' => 'Jilid', 'slug' => 'jilid-' . $novel->id,
            'status' => 'ongoing', 'position' => 1,
        ]);
        $book->chapters()->create(['title' => 'Bab', 'body' => 'isi', 'position' => 1]);

        $novel->delete();

        $this->assertSame(0, World::count());
        $this->assertSame(0, Book::count());
        $this->assertSame(0, \App\Models\Chapter::count());
        $this->assertSame(0, Character::count());

        Novel::onlyTrashed()->find($novel->id)->restore();

        $this->assertSame(1, World::count());
        $this->assertSame(1, Book::count());
        $this->assertSame(1, \App\Models\Chapter::count());
        $this->assertSame(1, Character::count());
    }

    public function test_a_soft_deleted_chapter_keeps_its_comments(): void
    {
        $owner = $this->author();
        $novel = $this->novelFor($owner);
        $book = Book::create([
            'novel_id' => $novel->id, 'title' => 'Jilid', 'slug' => 'jilid-c' . $novel->id,
            'status' => 'ongoing', 'position' => 1,
        ]);
        $chapter = $book->chapters()->create(['title' => 'Bab', 'position' => 1]);
        $comment = $chapter->comments()->create(['user_id' => $owner->id, 'body' => 'Bagus']);

        $chapter->delete();

        // A chapter in the bin must come back with its conversation.
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);

        \App\Models\Chapter::onlyTrashed()->find($chapter->id)->restore();
        $this->assertSame(1, $chapter->fresh()->comments()->count());
    }

    public function test_a_soft_deleted_location_keeps_character_links(): void
    {
        $owner = $this->author();
        $world = $this->worldFor($this->novelFor($owner), $owner);
        $benua = Benua::create(['world_id' => $world->id, 'name' => 'Benua']);
        $character = $world->characters()->create([
            'name' => 'Tokoh',
            'origin_type' => $benua->getMorphClass(),
            'origin_id' => $benua->id,
        ]);

        $benua->delete();

        // The row still exists, so the link must not have been cleared —
        // otherwise restoring the location could never put it back.
        $this->assertSame($benua->id, $character->fresh()->origin_id);

        Benua::onlyTrashed()->find($benua->id)->restore();
        $this->assertSame($benua->id, $character->fresh()->origin_id);
    }

    public function test_a_permanent_delete_does_clear_character_links(): void
    {
        $owner = $this->author();
        $world = $this->worldFor($this->novelFor($owner), $owner);
        $benua = Benua::create(['world_id' => $world->id, 'name' => 'Benua']);
        $character = $world->characters()->create([
            'name' => 'Tokoh',
            'origin_type' => $benua->getMorphClass(),
            'origin_id' => $benua->id,
        ]);

        $benua->forceDelete();

        $this->assertNull($character->fresh()->origin_id);
        $this->assertNull($character->fresh()->origin_type);
    }

    public function test_purging_a_world_destroys_it_and_its_lore_for_good(): void
    {
        $owner = $this->author();
        $world = $this->worldFor($this->novelFor($owner), $owner);
        $world->characters()->create(['name' => 'Tokoh']);
        $world->loreEntries()->create(['title' => 'Sihir']);
        Benua::create(['world_id' => $world->id, 'name' => 'Benua']);

        $world->delete();
        World::onlyTrashed()->find($world->id)->purge();

        $this->assertSame(0, World::withTrashed()->count());
        $this->assertSame(0, Character::withTrashed()->count());
        $this->assertSame(0, LoreEntry::withTrashed()->count());
        $this->assertSame(0, Benua::withTrashed()->count());
    }

    public function test_the_trash_page_lists_roots_not_their_fallout(): void
    {
        $owner = $this->author();
        $world = $this->worldFor($this->novelFor($owner), $owner);
        $world->characters()->create(['name' => 'Ikut Terseret']);

        $world->delete();

        $response = $this->actingAs($owner)->get(route('trash.index'));

        $response->assertOk()
            ->assertSee($world->name)
            // The character went down with the world; listing it separately
            // would invite restoring it into a world that is still gone.
            ->assertDontSee('Ikut Terseret');
    }

    public function test_restoring_from_the_trash_page_works(): void
    {
        $owner = $this->author();
        $world = $this->worldFor($this->novelFor($owner), $owner);
        $world->characters()->create(['name' => 'Tokoh']);
        $world->delete();

        $this->actingAs($owner)
            ->patch(route('trash.restore', ['dunia', $world->id]))
            ->assertRedirect();

        $this->assertSame(1, World::count());
        $this->assertSame(1, Character::count());
    }

    public function test_a_stranger_cannot_see_or_touch_anothers_trash(): void
    {
        $owner = $this->author();
        $world = $this->worldFor($this->novelFor($owner), $owner);
        $world->delete();

        $stranger = $this->author();

        $this->actingAs($stranger)->get(route('trash.index'))
            ->assertOk()
            ->assertDontSee($world->name);

        $this->actingAs($stranger)->patch(route('trash.restore', ['dunia', $world->id]))->assertForbidden();
        $this->actingAs($stranger)->delete(route('trash.destroy', ['dunia', $world->id]))->assertForbidden();

        $this->assertSame(1, World::onlyTrashed()->count());
    }

    public function test_an_unknown_trash_type_is_rejected(): void
    {
        $owner = $this->author();

        $this->actingAs($owner)->patch(route('trash.restore', ['users', 1]))->assertNotFound();
    }

    public function test_a_title_can_be_reused_after_its_record_is_binned(): void
    {
        // A row in the bin still occupies its slug in the unique index. If
        // uniqueSlug() cannot see it, recreating something under the same
        // title dies on a duplicate key — and deleting-then-recreating is
        // exactly what an author does after a false start.
        $owner = $this->author();

        $this->actingAs($owner)->post(route('novels.store'), [
            'title' => 'Caldevara', 'status' => 'active',
        ])->assertRedirect();

        $first = Novel::where('title', 'Caldevara')->sole();
        $this->actingAs($owner)->delete(route('novels.destroy', $first))->assertRedirect();

        // Same title again — must succeed, with a slug of its own.
        $this->actingAs($owner)->post(route('novels.store'), [
            'title' => 'Caldevara', 'status' => 'active',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $second = Novel::where('title', 'Caldevara')->sole();
        $this->assertNotSame($first->slug, $second->slug);
    }

    public function test_deleted_records_disappear_from_their_normal_listings(): void
    {
        $owner = $this->author();
        $novel = $this->novelFor($owner);
        $world = $this->worldFor($novel, $owner);
        $world->delete();

        $this->actingAs($owner)->get(route('worlds.index'))
            ->assertOk()
            ->assertDontSee($world->name);

        $this->actingAs($owner)->get(route('novels.show', $novel))
            ->assertOk()
            ->assertDontSee($world->name);
    }
}
