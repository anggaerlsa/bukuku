<?php

namespace Tests\Feature;

use App\Models\Benua;
use App\Models\Book;
use App\Models\Novel;
use App\Models\User;
use App\Models\World;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One search box across characters, places, factions, lore and the manuscript.
 *
 * The rule that matters most here is not "does it find things" but "whose
 * things does it find": search touches every table at once, so it is the
 * easiest place in the app to leak another author's private novel.
 */
class SearchTest extends TestCase
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

    private function novelFor(User $user, bool $shared = false): Novel
    {
        $n = ++$this->seq;

        return Novel::create([
            'user_id' => $user->id,
            'title' => "Novel {$n}",
            'slug' => "novel-{$n}",
            'status' => 'active',
            'is_shared' => $shared,
            'shared_at' => $shared ? now() : null,
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

    public function test_it_finds_records_of_every_kind(): void
    {
        $owner = $this->author();
        $novel = $this->novelFor($owner);
        $world = $this->worldFor($novel, $owner);

        $world->characters()->create(['name' => 'Kalandra Sang Penjaga']);
        $world->loreEntries()->create(['title' => 'Sihir Kalandra', 'body' => 'x']);
        $world->organizations()->create(['name' => 'Ordo Kalandra', 'status' => 'aktif']);
        Benua::create(['world_id' => $world->id, 'name' => 'Benua Kalandra']);

        $book = Book::create([
            'novel_id' => $novel->id, 'title' => 'Jilid', 'slug' => 'jilid-' . $novel->id,
            'status' => 'ongoing', 'position' => 1,
        ]);
        $book->chapters()->create(['title' => 'Bab Satu', 'body' => 'Ia memanggil Kalandra.', 'position' => 1]);

        $this->actingAs($owner)->get(route('search.index', ['q' => 'Kalandra']))
            ->assertOk()
            ->assertSee('Kalandra Sang Penjaga')
            ->assertSee('Sihir Kalandra')
            ->assertSee('Ordo Kalandra')
            ->assertSee('Benua Kalandra')
            ->assertSee('Bab Satu');
    }

    public function test_it_searches_inside_a_chapter_body(): void
    {
        // Half-remembering a line and hunting for it is a big part of why an
        // author searches at all.
        $owner = $this->author();
        $novel = $this->novelFor($owner);
        $book = Book::create([
            'novel_id' => $novel->id, 'title' => 'Jilid', 'slug' => 'jilid-b' . $novel->id,
            'status' => 'ongoing', 'position' => 1,
        ]);
        $book->chapters()->create([
            'title' => 'Bab Tanpa Petunjuk',
            'body' => 'Langit di atas Nbulteria berwarna tembaga.',
            'position' => 1,
        ]);

        $this->actingAs($owner)->get(route('search.index', ['q' => 'tembaga']))
            ->assertOk()
            ->assertSee('Bab Tanpa Petunjuk');
    }

    public function test_it_never_reaches_into_another_authors_private_novel(): void
    {
        $owner = $this->author();
        $world = $this->worldFor($this->novelFor($owner, shared: false), $owner);
        $world->characters()->create(['name' => 'Rahasia Besar']);
        $world->loreEntries()->create(['title' => 'Rahasia Sihir', 'body' => 'x']);

        $stranger = $this->author();

        $this->actingAs($stranger)->get(route('search.index', ['q' => 'Rahasia']))
            ->assertOk()
            ->assertDontSee('Rahasia Besar')
            ->assertDontSee('Rahasia Sihir');
    }

    public function test_a_shared_novel_becomes_searchable_by_members(): void
    {
        $owner = $this->author();
        $world = $this->worldFor($this->novelFor($owner, shared: true), $owner);
        $world->characters()->create(['name' => 'Terbuka Untuk Umum']);

        $member = $this->author();

        $this->actingAs($member)->get(route('search.index', ['q' => 'Terbuka']))
            ->assertOk()
            ->assertSee('Terbuka Untuk Umum');
    }

    public function test_binned_records_do_not_show_up(): void
    {
        $owner = $this->author();
        $world = $this->worldFor($this->novelFor($owner), $owner);
        $character = $world->characters()->create(['name' => 'Sudah Dibuang']);

        $character->delete();

        $this->actingAs($owner)->get(route('search.index', ['q' => 'Dibuang']))
            ->assertOk()
            ->assertDontSee('Sudah Dibuang');
    }

    public function test_an_empty_query_just_shows_the_form(): void
    {
        $this->actingAs($this->author())->get(route('search.index'))
            ->assertOk()
            ->assertSee('Ketik sesuatu untuk mulai mencari');
    }

    public function test_wildcards_are_treated_as_plain_text(): void
    {
        // A bare % would otherwise match every row in every table.
        $owner = $this->author();
        $world = $this->worldFor($this->novelFor($owner), $owner);
        $world->characters()->create(['name' => 'Tokoh Biasa']);

        $this->actingAs($owner)->get(route('search.index', ['q' => '%']))
            ->assertOk()
            ->assertDontSee('Tokoh Biasa');
    }

    public function test_it_says_so_when_nothing_matches(): void
    {
        $this->actingAs($this->author())->get(route('search.index', ['q' => 'tidakadaapapun']))
            ->assertOk()
            ->assertSee('Tidak ada yang cocok');
    }
}
