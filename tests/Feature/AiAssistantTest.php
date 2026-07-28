<?php

namespace Tests\Feature;

use App\Models\AiConversation;
use App\Models\AiCredential;
use App\Models\Book;
use App\Models\Novel;
use App\Models\User;
use App\Models\World;
use App\Support\NovelDossier;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The writing assistant.
 *
 * Two claims are worth pinning here above all others, because both are
 * invisible from the code that uses them:
 *
 * 1. A question about novel A is answered from novel A alone. The tests build
 *    two novels under the SAME author, so ownership cannot be what separates
 *    them — only the dossier's scoping can — and then read the actual request
 *    body that would go to the provider.
 *
 * 2. The assistant belongs to the author, not to readers or administrators. A
 *    shared novel does not share it, and a superadmin does not inherit it even
 *    though Gate::before hands them every other ability in the app.
 */
class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        config(['ai.enabled' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | Fixtures
    |--------------------------------------------------------------------------
    */

    private function author(): User
    {
        return tap(User::factory()->create(), fn (User $u) => $u->syncRoles(['author']));
    }

    /** A novel with one world, one character, one lore article and one chapter. */
    private function novelFor(User $user, string $marker): Novel
    {
        $n = ++$this->seq;

        $novel = Novel::create([
            'user_id' => $user->id,
            'title' => "Novel {$marker}",
            'slug' => "novel-{$marker}-{$n}",
            'status' => 'active',
            'synopsis' => "Sinopsis {$marker}",
        ]);

        $world = World::create([
            'user_id' => $user->id,
            'novel_id' => $novel->id,
            'name' => "Dunia {$marker}",
            'slug' => "dunia-{$marker}-{$n}",
            'status' => 'active',
        ]);

        $world->characters()->create(['name' => "Tokoh{$marker}", 'backstory' => "Latar{$marker}"]);
        $world->loreEntries()->create(['title' => "Lore{$marker}", 'body' => "Isi lore {$marker}"]);

        $book = Book::create([
            'novel_id' => $novel->id,
            'title' => "Buku{$marker}",
            'slug' => "buku-{$marker}-{$n}",
            'status' => 'active',
            'position' => 1,
        ]);

        $book->chapters()->create([
            'title' => "Bab{$marker}",
            'body' => "Naskah{$marker} " . str_repeat('kata ', 50),
            'position' => 1,
        ]);

        return $novel->fresh();
    }

    /** An author who is ready to ask: key saved, consent given, novel armed. */
    private function readyAuthor(): array
    {
        $user = $this->author();
        $user->update(['ai_consented_at' => now(), 'ai_consent_version' => User::AI_CONSENT_VERSION]);

        $credential = new AiCredential(['user_id' => $user->id, 'provider' => 'deepseek', 'model' => 'deepseek-v4-flash']);
        $credential->fillKey('sk-kunci-uji-0000000000000000');
        $credential->verified_at = now();
        $credential->save();

        $novel = $this->novelFor($user, 'Alfa');
        $novel->update(['ai_enabled' => true]);

        return [$user, $novel->fresh()];
    }

    private function fakeProvider(string $answer = 'Jawaban uji.'): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'model' => 'deepseek-v4-flash',
                'choices' => [['message' => ['role' => 'assistant', 'content' => $answer]]],
                'usage' => [
                    'prompt_tokens' => 1200,
                    'completion_tokens' => 34,
                    'prompt_cache_hit_tokens' => 1000,
                ],
            ]),
            '*/models' => Http::response(['data' => [['id' => 'deepseek-v4-flash']]]),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | The lock: one novel per dossier
    |--------------------------------------------------------------------------
    */

    public function test_a_dossier_holds_nothing_from_another_novel(): void
    {
        $author = $this->author();
        // Both novels belong to the same author on purpose: if isolation only
        // worked because of ownership, this test would not notice.
        $alfa = $this->novelFor($author, 'Alfa');
        $this->novelFor($author, 'Beta');

        $dossier = NovelDossier::for($alfa);

        $this->assertStringContainsString('TokohAlfa', $dossier->text);
        $this->assertStringContainsString('LoreAlfa', $dossier->text);
        $this->assertStringContainsString('NaskahAlfa', $dossier->text);

        foreach (['TokohBeta', 'LoreBeta', 'NaskahBeta', 'Novel Beta', 'Dunia Beta', 'Sinopsis Beta'] as $secret) {
            $this->assertStringNotContainsString($secret, $dossier->text, "Dossier bocor: {$secret}");
        }
    }

    public function test_the_request_sent_to_the_provider_carries_only_this_novel(): void
    {
        [$user, $novel] = $this->readyAuthor();
        $this->novelFor($user, 'Beta');
        $this->fakeProvider();

        $this->actingAs($user)
            ->post(route('ai.store', $novel), ['question' => 'Siapa tokohnya?'])
            ->assertRedirect();

        Http::assertSent(function ($request) {
            $body = json_encode($request->data());

            $this->assertStringContainsString('TokohAlfa', $body);
            $this->assertStringNotContainsString('TokohBeta', $body);
            $this->assertStringNotContainsString('NaskahBeta', $body);

            return true;
        });
    }

    public function test_a_conversation_cannot_be_opened_through_another_novel(): void
    {
        [$user, $alfa] = $this->readyAuthor();
        $beta = $this->novelFor($user, 'Beta');

        $thread = AiConversation::create([
            'novel_id' => $alfa->id,
            'user_id' => $user->id,
            'provider' => 'deepseek',
        ]);

        // Alfa's transcript reached for through Beta's URL: the id exists, but
        // not under this novel.
        $this->actingAs($user)->get(route('ai.show', [$beta, $thread]))->assertNotFound();
        $this->actingAs($user)->get(route('ai.show', [$alfa, $thread]))->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | Who may use it
    |--------------------------------------------------------------------------
    */

    public function test_only_the_author_may_open_the_assistant(): void
    {
        [, $novel] = $this->readyAuthor();
        $stranger = $this->author();

        $this->actingAs($stranger)->get(route('ai.index', $novel))->assertForbidden();
        $this->actingAs($stranger)->post(route('ai.store', $novel), ['question' => 'Halo'])->assertForbidden();
        $this->actingAs($stranger)->patch(route('ai.toggle', $novel), ['ai_enabled' => 0])->assertForbidden();
    }

    public function test_sharing_a_novel_does_not_share_its_assistant(): void
    {
        [, $novel] = $this->readyAuthor();
        $novel->update(['is_shared' => true, 'shared_at' => now()]);
        $reader = $this->author();

        // The reader may read it...
        $this->actingAs($reader)->get(route('novels.show', $novel))->assertOk();
        // ...but the assistant is a writing tool, not a reading one.
        $this->actingAs($reader)->get(route('ai.index', $novel))->assertForbidden();
    }

    public function test_a_superadmin_does_not_inherit_the_assistant(): void
    {
        [, $novel] = $this->readyAuthor();
        $admin = tap(User::factory()->create(), fn (User $u) => $u->syncRoles(['superadmin']));

        // Gate::before gives a superadmin every other ability in the app; this
        // one is deliberately checked without the Gate for exactly that reason.
        $this->actingAs($admin)->get(route('ai.index', $novel))->assertForbidden();
        $this->actingAs($admin)->post(route('ai.store', $novel), ['question' => 'Halo'])->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | The gates in front of a question
    |--------------------------------------------------------------------------
    */

    public function test_asking_needs_the_novel_armed(): void
    {
        [$user, $novel] = $this->readyAuthor();
        $novel->update(['ai_enabled' => false]);

        $this->actingAs($user)->post(route('ai.store', $novel), ['question' => 'Halo'])->assertForbidden();
    }

    public function test_asking_needs_a_saved_key(): void
    {
        [$user, $novel] = $this->readyAuthor();
        $user->aiCredentials()->delete();

        $this->actingAs($user)->post(route('ai.store', $novel), ['question' => 'Halo'])->assertForbidden();
        $this->actingAs($user)->get(route('ai.index', $novel))
            ->assertOk()
            ->assertSee('Asisten belum siap dipakai');
    }

    public function test_asking_needs_consent(): void
    {
        [$user, $novel] = $this->readyAuthor();
        $user->update(['ai_consented_at' => null, 'ai_consent_version' => null]);

        $this->actingAs($user)->post(route('ai.store', $novel), ['question' => 'Halo'])->assertForbidden();
    }

    public function test_a_bumped_consent_version_asks_again(): void
    {
        [$user] = $this->readyAuthor();
        $user->update(['ai_consent_version' => User::AI_CONSENT_VERSION - 1]);

        $this->assertFalse($user->fresh()->hasAiConsent());
    }

    /*
    |--------------------------------------------------------------------------
    | Asking and answering
    |--------------------------------------------------------------------------
    */

    public function test_a_question_and_its_answer_are_stored_against_the_novel(): void
    {
        [$user, $novel] = $this->readyAuthor();
        $this->fakeProvider('Tokohnya TokohAlfa.');

        $this->actingAs($user)
            ->post(route('ai.store', $novel), ['question' => 'Siapa tokoh utamanya?', 'with_manuscript' => 1])
            ->assertRedirect();

        $thread = AiConversation::sole();
        $this->assertSame($novel->id, $thread->novel_id);
        $this->assertSame($user->id, $thread->user_id);
        $this->assertSame('Siapa tokoh utamanya?', $thread->title);
        $this->assertTrue($thread->with_manuscript);

        $messages = $thread->messages()->get();
        $this->assertCount(2, $messages);
        $this->assertSame('user', $messages[0]->role);
        $this->assertSame('Siapa tokoh utamanya?', $messages[0]->content);
        $this->assertSame('assistant', $messages[1]->role);
        $this->assertSame('Tokohnya TokohAlfa.', $messages[1]->content);
        $this->assertSame(1200, $messages[1]->tokens_in);
        $this->assertSame(34, $messages[1]->tokens_out);
        $this->assertSame(1000, $messages[1]->tokens_cached);
    }

    public function test_a_failed_request_stores_nothing_and_hands_the_question_back(): void
    {
        [$user, $novel] = $this->readyAuthor();
        Http::fake(['*/chat/completions' => Http::response(['error' => ['message' => 'no balance']], 402)]);

        $this->actingAs($user)
            ->from(route('ai.index', $novel))
            ->post(route('ai.store', $novel), ['question' => 'Pertanyaan yang gagal'])
            ->assertRedirect(route('ai.index', $novel))
            ->assertSessionHas('error')
            ->assertSessionHasInput('question', 'Pertanyaan yang gagal');

        // A question with no answer must not be left in the transcript: it
        // would be re-sent as context on the next turn.
        $this->assertSame(0, \App\Models\AiMessage::count());
    }

    public function test_a_thread_without_the_manuscript_never_sends_chapters(): void
    {
        [$user, $novel] = $this->readyAuthor();
        $this->fakeProvider();

        $this->actingAs($user)
            ->post(route('ai.store', $novel), ['question' => 'Tanpa naskah', 'with_manuscript' => 0])
            ->assertRedirect();

        $this->assertFalse(AiConversation::sole()->with_manuscript);

        Http::assertSent(function ($request) {
            $body = json_encode($request->data());

            $this->assertStringContainsString('TokohAlfa', $body);
            $this->assertStringNotContainsString('NaskahAlfa', $body);

            return true;
        });
    }

    public function test_the_assistant_page_starts_a_blank_thread(): void
    {
        [$user, $novel] = $this->readyAuthor();
        $this->fakeProvider('Jawaban lama.');

        $this->actingAs($user)->post(route('ai.store', $novel), ['question' => 'Pertanyaan lama'])->assertRedirect();
        $thread = AiConversation::sole();

        // The thread is listed, but its transcript is not reopened — this URL is
        // where "Percakapan baru" goes.
        $this->actingAs($user)->get(route('ai.index', $novel))
            ->assertOk()
            ->assertSee('Pertanyaan lama')      // in the sidebar, as the title
            ->assertDontSee('Jawaban lama.');   // the transcript itself is not

        $this->actingAs($user)->get(route('ai.show', [$novel, $thread]))
            ->assertOk()
            ->assertSee('Jawaban lama.');
    }

    /*
    |--------------------------------------------------------------------------
    | Trimming an oversized novel
    |--------------------------------------------------------------------------
    */

    public function test_an_oversized_manuscript_is_trimmed_and_says_where(): void
    {
        $author = $this->author();
        $novel = $this->novelFor($author, 'Alfa');
        $book = $novel->books()->sole();

        foreach (range(2, 5) as $position) {
            $book->chapters()->create([
                'title' => "Bab ke-{$position}",
                'body' => str_repeat('panjang ', 400),
                'position' => $position,
            ]);
        }

        // Room for the lore and a chapter or two, not for all five.
        config(['ai.context_budget' => 1500, 'ai.chars_per_token' => 3.0]);

        $dossier = NovelDossier::for($novel);

        $this->assertTrue($dossier->wasTrimmed());
        $this->assertSame(5, $dossier->chaptersTotal);
        $this->assertLessThan(5, $dossier->chaptersIncluded);
        $this->assertStringContainsString('bab tidak ikut terkirim', $dossier->trimmedFrom);
    }

    /*
    |--------------------------------------------------------------------------
    | The key itself
    |--------------------------------------------------------------------------
    */

    public function test_a_key_is_only_stored_once_the_provider_accepts_it(): void
    {
        $user = $this->author();
        Http::fake(['*/models' => Http::response(['error' => ['message' => 'bad key']], 401)]);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.ai.update'), [
                'api_key' => 'sk-kunci-yang-salah-000000000',
                'model' => 'deepseek-v4-flash',
                'setuju' => 1,
            ])
            ->assertSessionHasErrors('api_key');

        $this->assertSame(0, AiCredential::count());
        // A rejected key must not bank the consent either — the author agreed
        // in the same submission that failed.
        $this->assertNull($user->fresh()->ai_consented_at);
    }

    public function test_a_key_is_stored_encrypted_and_never_rendered(): void
    {
        $user = $this->author();
        $this->fakeProvider();
        $plain = 'sk-kunci-rahasia-abcdefgh1234';

        $this->actingAs($user)->put(route('profile.ai.update'), [
            'api_key' => $plain,
            'model' => 'deepseek-v4-flash',
            'setuju' => 1,
        ])->assertSessionHasNoErrors();

        $credential = AiCredential::sole();
        $this->assertSame($plain, $credential->api_key);
        $this->assertSame('1234', $credential->key_tail);
        $this->assertNotNull($credential->verified_at);
        $this->assertTrue($user->fresh()->hasAiConsent());

        // Ciphertext on disk, not the key.
        $stored = DB::table('ai_credentials')->where('id', $credential->id)->value('api_key');
        $this->assertNotSame($plain, $stored);
        $this->assertStringNotContainsString('rahasia', $stored);

        // And the page shows a fragment, never the key.
        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee($plain)
            ->assertSee('••••••••1234');
    }

    public function test_consent_is_required_before_a_key_can_be_saved(): void
    {
        $user = $this->author();
        $this->fakeProvider();

        $this->actingAs($user)->put(route('profile.ai.update'), [
            'api_key' => 'sk-kunci-tanpa-persetujuan-00',
            'model' => 'deepseek-v4-flash',
        ])->assertSessionHasErrors('setuju');

        $this->assertSame(0, AiCredential::count());
        Http::assertNothingSent();
    }

    public function test_deleting_a_key_keeps_the_consent(): void
    {
        [$user] = $this->readyAuthor();

        $this->actingAs($user)->delete(route('profile.ai.destroy'))->assertRedirect();

        $this->assertSame(0, AiCredential::count());
        $this->assertTrue($user->fresh()->hasAiConsent());
    }

    /*
    |--------------------------------------------------------------------------
    | The bin
    |--------------------------------------------------------------------------
    */

    public function test_binning_a_novel_takes_its_conversations_and_restore_brings_them_back(): void
    {
        [$user, $novel] = $this->readyAuthor();
        $this->fakeProvider();

        $this->actingAs($user)->post(route('ai.store', $novel), ['question' => 'Halo'])->assertRedirect();
        $this->assertSame(1, AiConversation::count());

        $novel->delete();
        $this->assertSame(0, AiConversation::count());
        $this->assertSame(1, AiConversation::onlyTrashed()->count());

        Novel::onlyTrashed()->find($novel->id)->restore();
        $this->assertSame(1, AiConversation::count());
    }

    public function test_purging_a_novel_removes_its_transcript_for_good(): void
    {
        [$user, $novel] = $this->readyAuthor();
        $this->fakeProvider();

        $this->actingAs($user)->post(route('ai.store', $novel), ['question' => 'Halo'])->assertRedirect();

        $novel->purge();

        $this->assertSame(0, AiConversation::withTrashed()->count());
        $this->assertSame(0, \App\Models\AiMessage::count());
    }
}
