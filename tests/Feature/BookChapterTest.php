<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Novel;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Buku (volumes) and Bab (chapters) — the manuscript itself.
 *
 * The rules worth pinning down are the ones a form cannot show you: the word
 * count must follow the text, reading order must not follow insertion order,
 * a volume must not quietly take its chapters down with it, and a shared
 * novel must be readable without becoming writable.
 */
class BookChapterTest extends TestCase
{
    use RefreshDatabase;

    private function author(): User
    {
        return tap(User::factory()->create(), fn (User $u) => $u->syncRoles(['author']));
    }

    private function novelFor(User $user): Novel
    {
        return Novel::create([
            'user_id' => $user->id,
            'title' => 'Novel Uji',
            'slug' => 'novel-uji',
            'status' => 'active',
        ]);
    }

    private function bookFor(Novel $novel): Book
    {
        return Book::create([
            'novel_id' => $novel->id,
            'title' => 'Jilid Uji',
            'slug' => 'jilid-uji',
            'status' => 'ongoing',
            'position' => 1,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_word_count_is_derived_from_the_text_not_supplied(): void
    {
        $book = $this->bookFor($this->novelFor($this->author()));

        $chapter = $book->chapters()->create([
            'title' => 'Bab',
            'body' => 'satu dua tiga empat lima',
            'position' => 1,
            'word_count' => 9999,   // sengaja salah
        ]);

        $this->assertSame(5, $chapter->fresh()->word_count);
    }

    public function test_word_count_follows_an_edit(): void
    {
        $book = $this->bookFor($this->novelFor($this->author()));
        $chapter = $book->chapters()->create(['title' => 'Bab', 'body' => 'satu dua tiga', 'position' => 1]);

        $chapter->update(['body' => 'satu dua']);

        $this->assertSame(2, $chapter->fresh()->word_count);
    }

    public function test_an_empty_chapter_counts_zero(): void
    {
        $book = $this->bookFor($this->novelFor($this->author()));

        foreach ([null, '', "  \n  "] as $i => $body) {
            $chapter = $book->chapters()->create(['title' => "Bab {$i}", 'body' => $body, 'position' => $i + 1]);
            $this->assertSame(0, $chapter->fresh()->word_count);
        }
    }

    public function test_chapters_read_in_position_order_not_insertion_order(): void
    {
        $book = $this->bookFor($this->novelFor($this->author()));

        $book->chapters()->create(['title' => 'Ketiga', 'position' => 3]);
        $book->chapters()->create(['title' => 'Pertama', 'position' => 1]);
        $book->chapters()->create(['title' => 'Kedua', 'position' => 2]);

        $this->assertSame(
            ['Pertama', 'Kedua', 'Ketiga'],
            $book->chapters()->pluck('title')->all()
        );
    }

    public function test_previous_and_next_walk_the_reading_order(): void
    {
        $book = $this->bookFor($this->novelFor($this->author()));
        $one = $book->chapters()->create(['title' => 'Satu', 'position' => 1]);
        $two = $book->chapters()->create(['title' => 'Dua', 'position' => 2]);
        $three = $book->chapters()->create(['title' => 'Tiga', 'position' => 3]);

        $this->assertNull($one->previous());
        $this->assertSame($two->id, $one->next()->id);
        $this->assertSame($one->id, $two->previous()->id);
        $this->assertSame($three->id, $two->next()->id);
        $this->assertNull($three->next());
    }

    public function test_a_volume_holding_chapters_refuses_to_be_deleted(): void
    {
        $author = $this->author();
        $book = $this->bookFor($this->novelFor($author));
        $book->chapters()->create(['title' => 'Bab', 'position' => 1]);

        $this->actingAs($author)
            ->delete(route('books.destroy', $book))
            ->assertRedirect();

        $this->assertModelExists($book);
        $this->assertSame(1, $book->chapters()->count());
    }

    public function test_an_empty_volume_can_be_deleted(): void
    {
        $author = $this->author();
        $book = $this->bookFor($this->novelFor($author));

        $this->actingAs($author)->delete(route('books.destroy', $book))->assertRedirect();

        $this->assertModelMissing($book);
    }

    public function test_a_stranger_can_neither_read_nor_write(): void
    {
        $book = $this->bookFor($this->novelFor($this->author()));
        $chapter = $book->chapters()->create(['title' => 'Bab', 'body' => 'rahasia', 'position' => 1]);
        $stranger = $this->author();

        $this->actingAs($stranger)->get(route('books.show', $book))->assertForbidden();
        $this->actingAs($stranger)->get(route('chapters.show', [$book, $chapter]))->assertForbidden();
    }

    public function test_sharing_opens_reading_and_nothing_else(): void
    {
        $owner = $this->author();
        $novel = $this->novelFor($owner);
        $novel->update(['is_shared' => true, 'shared_at' => now()]);

        $book = $this->bookFor($novel);
        $chapter = $book->chapters()->create(['title' => 'Bab', 'body' => 'terbaca', 'position' => 1]);
        $member = $this->author();

        $this->actingAs($member)->get(route('books.show', $book))->assertOk();
        $this->actingAs($member)->get(route('chapters.show', [$book, $chapter]))->assertSee('terbaca');

        // Every write path an outsider might try.
        $this->actingAs($member)->get(route('books.edit', $book))->assertForbidden();
        $this->actingAs($member)->get(route('chapters.create', $book))->assertForbidden();
        $this->actingAs($member)->post(route('chapters.store', $book), ['title' => 'Sisipan'])->assertForbidden();
        $this->actingAs($member)->delete(route('chapters.destroy', [$book, $chapter]))->assertForbidden();
        $this->actingAs($member)->delete(route('books.destroy', $book))->assertForbidden();

        // Nothing moved.
        $this->assertSame(1, $book->chapters()->count());
        $this->assertSame('Bab', $chapter->fresh()->title);
    }

    public function test_the_novel_page_lists_the_table_of_contents(): void
    {
        $author = $this->author();
        $novel = $this->novelFor($author);
        $book = $this->bookFor($novel);
        $book->chapters()->create(['title' => 'Bab Pembuka', 'body' => 'satu dua tiga', 'position' => 1]);

        $this->actingAs($author)
            ->get(route('novels.show', $novel))
            ->assertOk()
            ->assertSee('Jilid Uji')
            ->assertSee('Bab Pembuka');
    }

    public function test_deleting_a_novel_takes_its_books_and_chapters(): void
    {
        $author = $this->author();
        $novel = $this->novelFor($author);
        $book = $this->bookFor($novel);
        $chapter = $book->chapters()->create(['title' => 'Bab', 'position' => 1]);

        // Cascade at the database level, the same as worlds and lore.
        $novel->delete();

        $this->assertModelMissing($book);
        $this->assertNull(Chapter::find($chapter->id));
    }
}
