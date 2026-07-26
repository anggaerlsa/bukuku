<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Comment;
use App\Models\Novel;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comments on a book, and one level of replies.
 *
 * The rules worth pinning: only someone who can READ a book may comment (so a
 * shared novel opens to its members and a private one does not), a reply may
 * only attach to a top-level comment of the same book, editing is the
 * author's alone, and the novel's owner may delete anything on their book
 * while an outsider may delete nothing.
 */
class CommentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function author(): User
    {
        return tap(User::factory()->create(), fn (User $u) => $u->syncRoles(['author']));
    }

    /** Unique across the test regardless of who owns what. */
    private int $seq = 0;

    private function novelFor(User $user, bool $shared = false): Novel
    {
        $n = ++$this->seq;

        return Novel::create([
            'user_id' => $user->id,
            'title' => "Novel Uji {$n}",
            'slug' => "novel-uji-{$n}",
            'status' => 'active',
            'is_shared' => $shared,
            'shared_at' => $shared ? now() : null,
        ]);
    }

    private function bookFor(Novel $novel): Book
    {
        return Book::create([
            'novel_id' => $novel->id,
            'title' => 'Jilid Uji',
            'slug' => 'jilid-uji-' . $novel->id,
            'status' => 'ongoing',
            'position' => 1,
        ]);
    }

    public function test_the_owner_can_comment_on_their_own_book(): void
    {
        $owner = $this->author();
        $book = $this->bookFor($this->novelFor($owner));

        $this->actingAs($owner)
            ->post(route('comments.store', $book), ['body' => 'Catatan untuk diriku.'])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'book_id' => $book->id,
            'user_id' => $owner->id,
            'parent_id' => null,
            'body' => 'Catatan untuk diriku.',
        ]);
    }

    public function test_a_member_cannot_comment_on_a_private_novel(): void
    {
        $book = $this->bookFor($this->novelFor($this->author(), shared: false));
        $stranger = $this->author();

        $this->actingAs($stranger)
            ->post(route('comments.store', $book), ['body' => 'Menyusup.'])
            ->assertForbidden();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_a_member_can_comment_once_the_novel_is_shared(): void
    {
        $book = $this->bookFor($this->novelFor($this->author(), shared: true));
        $member = $this->author();

        $this->actingAs($member)
            ->post(route('comments.store', $book), ['body' => 'Bab pertamanya seru!'])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', ['user_id' => $member->id, 'body' => 'Bab pertamanya seru!']);
    }

    public function test_a_reply_attaches_to_a_top_level_comment(): void
    {
        $owner = $this->author();
        $book = $this->bookFor($this->novelFor($owner, shared: true));
        $top = $book->comments()->create(['user_id' => $owner->id, 'body' => 'Komentar utama']);
        $member = $this->author();

        $this->actingAs($member)
            ->post(route('comments.store', $book), ['body' => 'Setuju!', 'parent_id' => $top->id])
            ->assertRedirect();

        $reply = Comment::where('parent_id', $top->id)->sole();
        $this->assertSame('Setuju!', $reply->body);
        $this->assertTrue($reply->isReply());
    }

    public function test_a_reply_cannot_be_replied_to_so_threads_stay_one_level(): void
    {
        $owner = $this->author();
        $book = $this->bookFor($this->novelFor($owner, shared: true));
        $top = $book->comments()->create(['user_id' => $owner->id, 'body' => 'Utama']);
        $reply = $book->comments()->create(['user_id' => $owner->id, 'parent_id' => $top->id, 'body' => 'Balasan']);

        // parent_id must point at a TOP-LEVEL comment; a reply is rejected.
        $this->actingAs($this->author())
            ->post(route('comments.store', $book), ['body' => 'Balasan ke balasan', 'parent_id' => $reply->id])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_a_reply_cannot_borrow_another_books_comment(): void
    {
        $owner = $this->author();
        $bookA = $this->bookFor($this->novelFor($owner, shared: true));
        $bookB = $this->bookFor($this->novelFor($owner, shared: true));
        $onA = $bookA->comments()->create(['user_id' => $owner->id, 'body' => 'Di buku A']);

        $this->actingAs($this->author())
            ->post(route('comments.store', $bookB), ['body' => 'Nyasar', 'parent_id' => $onA->id])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_only_the_author_may_edit_their_comment(): void
    {
        $owner = $this->author();
        $book = $this->bookFor($this->novelFor($owner, shared: true));
        $member = $this->author();
        $comment = $book->comments()->create(['user_id' => $member->id, 'body' => 'Asli']);

        // The novel owner may moderate but may NOT rewrite someone's words.
        $this->actingAs($owner)
            ->patch(route('comments.update', $comment), ['body' => 'Diubah paksa'])
            ->assertForbidden();

        $this->actingAs($member)
            ->patch(route('comments.update', $comment), ['body' => 'Diperbaiki sendiri'])
            ->assertRedirect();

        $this->assertSame('Diperbaiki sendiri', $comment->fresh()->body);
    }

    public function test_the_novel_owner_may_delete_any_comment_on_their_book(): void
    {
        $owner = $this->author();
        $book = $this->bookFor($this->novelFor($owner, shared: true));
        $member = $this->author();
        $comment = $book->comments()->create(['user_id' => $member->id, 'body' => 'Spam']);

        $this->actingAs($owner)
            ->delete(route('comments.destroy', $comment))
            ->assertRedirect();

        $this->assertModelMissing($comment);
    }

    public function test_a_member_may_delete_their_own_comment_but_not_anothers(): void
    {
        $owner = $this->author();
        $book = $this->bookFor($this->novelFor($owner, shared: true));
        $a = $this->author();
        $b = $this->author();
        $ofA = $book->comments()->create(['user_id' => $a->id, 'body' => 'Milik A']);

        $this->actingAs($b)->delete(route('comments.destroy', $ofA))->assertForbidden();
        $this->assertModelExists($ofA);

        $this->actingAs($a)->delete(route('comments.destroy', $ofA))->assertRedirect();
        $this->assertModelMissing($ofA);
    }

    public function test_deleting_a_top_level_comment_takes_its_replies(): void
    {
        $owner = $this->author();
        $book = $this->bookFor($this->novelFor($owner, shared: true));
        $top = $book->comments()->create(['user_id' => $owner->id, 'body' => 'Utama']);
        $reply = $book->comments()->create(['user_id' => $this->author()->id, 'parent_id' => $top->id, 'body' => 'Balasan']);

        $this->actingAs($owner)->delete(route('comments.destroy', $top))->assertRedirect();

        $this->assertModelMissing($top);
        $this->assertModelMissing($reply);   // cascade
    }

    public function test_a_comment_is_marked_edited_only_after_an_edit(): void
    {
        $owner = $this->author();
        $book = $this->bookFor($this->novelFor($owner, shared: true));
        $comment = $book->comments()->create(['user_id' => $owner->id, 'body' => 'Awal']);

        // A brand-new comment is not "edited", even when created and read
        // inside the same second — this is why edited_at is a column, not a
        // created_at/updated_at comparison.
        $this->assertFalse($comment->wasEdited());
        $this->assertNull($comment->edited_at);

        $this->actingAs($owner)->patch(route('comments.update', $comment), ['body' => 'Diperbaiki']);

        $this->assertTrue($comment->fresh()->wasEdited());
        $this->assertNotNull($comment->fresh()->edited_at);
    }

    public function test_an_empty_comment_is_rejected(): void
    {
        $owner = $this->author();
        $book = $this->bookFor($this->novelFor($owner));

        $this->actingAs($owner)
            ->post(route('comments.store', $book), ['body' => '   '])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_deleting_a_book_takes_its_comments(): void
    {
        $owner = $this->author();
        $book = $this->bookFor($this->novelFor($owner));
        $comment = $book->comments()->create(['user_id' => $owner->id, 'body' => 'Menempel di buku']);

        $book->delete();

        $this->assertModelMissing($comment);
    }
}
