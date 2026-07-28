<?php

namespace App\Ai;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Support\NovelDossier;
use Illuminate\Support\Facades\DB;

/**
 * One turn of conversation about one novel.
 *
 * The dossier is rebuilt from the database on every request rather than stored
 * with the thread. It costs a couple of seconds and it is the only way an answer
 * can reflect a character the author edited five minutes ago — a cached copy
 * would quietly answer from the novel as it used to be.
 */
class NovelChat
{
    /**
     * The assistant's standing instructions.
     *
     * Kept as a constant and placed before the dossier so the whole system
     * message is byte-identical between requests: providers cache prompt
     * prefixes, and that cache is the difference between paying for 300,000
     * tokens and paying for a few hundred.
     *
     * "Say you don't know" is the important line. An assistant that invents a
     * plausible sibling for a character is worse than useless to someone trying
     * to keep 60 lore articles consistent.
     */
    private const INSTRUCTIONS = <<<'TXT'
        Kamu asisten penulis untuk SATU novel. Seluruh bahan yang kamu punya ada di dokumen di bawah:
        dunia, karakter, lokasi, organisasi, artikel lore, dan naskah novel ini.

        Aturan:
        - Jawab dalam bahasa Indonesia, ringkas dan langsung.
        - Jawab HANYA berdasarkan dokumen di bawah. Kalau sesuatu tidak ada di sana, katakan tidak ada
          di catatan — jangan mengarang nama, tempat, atau kejadian.
        - Bedakan dengan jelas antara "ini yang tertulis di catatan" dan "ini usulanku". Kalau penulis
          minta ide, tandai bagian yang usulan.
        - Kalau kamu menemukan dua catatan yang saling bertentangan, sebutkan keduanya beserta letaknya.
        - Sebut sumbernya kalau relevan (nama karakter, judul artikel lore, atau nomor bab).
        - Jangan menulis ulang naskah kecuali diminta.
        TXT;

    /**
     * Ask a question and store both sides of the exchange.
     *
     * Nothing is persisted until the provider has answered: a failed request
     * would otherwise leave a question hanging in the transcript with no reply,
     * which then gets sent again on the next turn.
     *
     * @throws AiException
     */
    public function reply(AiConversation $conversation, string $question): AiMessage
    {
        $credential = $conversation->user->aiCredentialFor($conversation->provider);

        if ($credential === null) {
            throw new AiException('Kunci API belum dipasang. Simpan kuncimu di halaman Profil dulu.');
        }

        $dossier = NovelDossier::for($conversation->novel, $conversation->with_manuscript);

        $messages = array_merge(
            [['role' => 'system', 'content' => self::INSTRUCTIONS . "\n\n" . $dossier->text]],
            $this->history($conversation),
            [['role' => 'user', 'content' => $question]],
        );

        $reply = Ai::driver($conversation->provider)->chat(
            $credential->api_key,
            $conversation->model ?: (string) $credential->model,
            $messages,
        );

        return DB::transaction(function () use ($conversation, $question, $reply) {
            $conversation->messages()->create([
                'role' => 'user',
                'content' => $question,
            ]);

            $answer = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $reply->content,
                'tokens_in' => $reply->tokensIn,
                'tokens_out' => $reply->tokensOut,
                'tokens_cached' => $reply->tokensCached,
            ]);

            $conversation->titleFrom($question);
            $conversation->touch();

            return $answer;
        });
    }

    /**
     * The recent transcript, oldest first.
     *
     * Capped because it grows without limit while the dossier in front of it
     * does not — and every message is re-sent on every turn. The page tells the
     * author when a conversation has grown past the cap; it is not dropped
     * quietly.
     *
     * @return list<array{role: string, content: string}>
     */
    private function history(AiConversation $conversation): array
    {
        return $conversation->messages()
            ->latest('id')
            ->limit(self::historyLimit())
            ->get()
            ->reverse()
            ->map(fn (AiMessage $message) => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->values()
            ->all();
    }

    public static function historyLimit(): int
    {
        return (int) config('ai.history_messages', 30);
    }
}
