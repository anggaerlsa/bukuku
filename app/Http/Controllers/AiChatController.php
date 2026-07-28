<?php

namespace App\Http\Controllers;

use App\Ai\Ai;
use App\Ai\AiException;
use App\Ai\NovelChat;
use App\Models\AiConversation;
use App\Models\Novel;
use App\Support\NovelDossier;
use Illuminate\Http\Request;

/**
 * The writing assistant, one novel at a time.
 *
 * Every action here starts from a route-bound Novel and refuses anyone who is
 * not its author. The context the assistant sees is assembled from that novel
 * alone (see NovelDossier), and a conversation carries `novel_id` as part of its
 * identity — so there is no request shape, valid or forged, that answers a
 * question about novel A using anything from novel B.
 */
class AiChatController extends Controller
{
    public function __construct(private readonly NovelChat $chat) {}

    /**
     * A blank thread, with the existing ones listed beside it. Deliberately not
     * "open the most recent": this URL is what "Percakapan baru" points at, and
     * it has to actually start one.
     */
    public function index(Request $request, Novel $novel)
    {
        $this->authorizeChat($request, $novel);

        return view('manage.ai.index', $this->pageData($request, $novel, null));
    }

    public function show(Request $request, Novel $novel, AiConversation $conversation)
    {
        $this->authorizeChat($request, $novel);
        $this->assertBelongsTo($novel, $conversation, $request);

        return view('manage.ai.index', $this->pageData($request, $novel, $conversation));
    }

    /**
     * Ask a question. The reply is fetched inline rather than queued: the author
     * is sitting there waiting for it, and a job would only move the wait.
     */
    public function store(Request $request, Novel $novel)
    {
        $this->authorizeChat($request, $novel);
        abort_unless($this->readiness($request, $novel)['ready'], 403);

        $data = $request->validate([
            'question' => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'integer'],
            'with_manuscript' => ['nullable', 'boolean'],
        ], [
            'question.required' => 'Tulis dulu pertanyaanmu.',
            'question.max' => 'Pertanyaan terlalu panjang — maksimal 4.000 karakter.',
        ]);

        $conversation = filled($data['conversation_id'] ?? null)
            ? $this->threadOrFail($novel, (int) $data['conversation_id'], $request)
            : $novel->aiConversations()->create([
                'user_id' => $request->user()->id,
                'provider' => config('ai.default'),
                'model' => $request->user()->aiCredentialFor()?->model,
                'with_manuscript' => $request->boolean('with_manuscript'),
            ]);

        try {
            $this->chat->reply($conversation, $data['question']);
        } catch (AiException $e) {
            // The question is handed back to the form so a failed request never
            // costs the author what they typed.
            return back()
                ->withInput(['question' => $data['question']])
                ->with('error', $e->getMessage());
        }

        return redirect()->route('ai.show', [$novel, $conversation]);
    }

    public function destroy(Request $request, Novel $novel, AiConversation $conversation)
    {
        $this->authorizeChat($request, $novel);
        $this->assertBelongsTo($novel, $conversation, $request);

        $conversation->delete();

        return redirect()->route('ai.index', $novel)
            ->with('status', 'Percakapan dipindahkan ke sampah bersama novelnya.');
    }

    /** Arm or disarm the assistant for this novel. */
    public function toggle(Request $request, Novel $novel)
    {
        $this->authorizeChat($request, $novel);

        $on = $request->boolean('ai_enabled');
        $novel->update(['ai_enabled' => $on]);

        return back()->with('status', $on
            ? "Asisten AI dinyalakan untuk \"{$novel->title}\"."
            : "Asisten AI dimatikan untuk \"{$novel->title}\".");
    }

    /*
    |--------------------------------------------------------------------------
    | Guards
    |--------------------------------------------------------------------------
    */

    /**
     * Only the novel's own author.
     *
     * Deliberately not $this->authorize(): Gate::before hands a superadmin
     * every ability in the app, and this is the one place that would be wrong.
     * The assistant runs on the signed-in user's personal API key and ships a
     * whole unpublished manuscript to a third party — being an administrator is
     * not a reason to do that with someone else's book. A novel shared
     * read-only does not qualify either: sharing is for readers, and this is a
     * tool for the person writing it.
     */
    private function authorizeChat(Request $request, Novel $novel): void
    {
        abort_unless($novel->user_id === $request->user()->id, 403);
    }

    /**
     * A conversation reached through a novel must belong to that novel AND to
     * the signed-in author. Without the first check an id from another novel
     * would load a transcript that this novel's dossier never produced.
     */
    private function assertBelongsTo(Novel $novel, AiConversation $conversation, Request $request): void
    {
        abort_unless(
            $conversation->novel_id === $novel->id && $conversation->user_id === $request->user()->id,
            404
        );
    }

    private function threadOrFail(Novel $novel, int $id, Request $request): AiConversation
    {
        return $novel->aiConversations()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Page state
    |--------------------------------------------------------------------------
    */

    private function pageData(Request $request, Novel $novel, ?AiConversation $conversation): array
    {
        $readiness = $this->readiness($request, $novel);

        // The dossier is only measured for display when the assistant can
        // actually be used — assembling it costs a second or two and reads the
        // whole manuscript, which is wasted work on a page that is only going
        // to say "pasang kuncimu dulu".
        //
        // Known cost: a send therefore builds it twice, once to ask and once to
        // render the page it redirects to (~2s each on a 117k-word novel over a
        // remote database). Kept deliberately — the alternative is a cached copy
        // that could show "73/73 bab" for a novel that has since outgrown the
        // budget, and a wrong reassurance is worse here than a slow page.
        $dossier = $readiness['ready']
            ? NovelDossier::for($novel, $conversation?->with_manuscript ?? true)
            : null;

        return [
            'novel' => $novel,
            'conversation' => $conversation,
            'conversations' => $novel->aiConversations()->where('user_id', $request->user()->id)->get(),
            'messages' => $conversation?->messages()->get() ?? collect(),
            'readiness' => $readiness,
            'dossier' => $dossier,
            'historyLimit' => NovelChat::historyLimit(),
        ];
    }

    /**
     * Everything that must be true before a question can be asked, kept as data
     * so the page can say which step is missing instead of failing at send.
     *
     * @return array{ready: bool, enabled: bool, consented: bool, credential: ?\App\Models\AiCredential, armed: bool}
     */
    private function readiness(Request $request, Novel $novel): array
    {
        $user = $request->user();
        $credential = $user->aiCredentialFor();

        $state = [
            'enabled' => Ai::enabled(),
            'consented' => $user->hasAiConsent(),
            'credential' => $credential,
            'armed' => (bool) $novel->ai_enabled,
        ];

        return $state + ['ready' => $state['enabled'] && $state['consented'] && $state['armed'] && $credential !== null];
    }
}
