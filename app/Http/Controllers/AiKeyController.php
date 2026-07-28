<?php

namespace App\Http\Controllers;

use App\Ai\Ai;
use App\Ai\AiException;
use App\Models\AiCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Where an author hands the app their own API key.
 *
 * The key is checked against the provider before it is stored, so a typo is
 * caught here instead of surfacing later as an unexplained failed chat. It is
 * saved encrypted and is never sent back to the browser — the form redisplays a
 * masked fragment built from a separate column, so nothing on this page can
 * leak a working key even if the response is cached somewhere it shouldn't be.
 */
class AiKeyController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $provider = (string) config('ai.default');

        $data = $request->validate([
            'api_key' => ['required', 'string', 'min:20', 'max:200'],
            'model' => ['required', 'string', 'in:' . implode(',', array_keys(Ai::models($provider)))],
            // Only asked while the author has not agreed to the current notice.
            // Re-asking on every key change would train them to click past it.
            'setuju' => $user->hasAiConsent() ? ['nullable'] : ['accepted'],
        ], [
            'api_key.required' => 'Kunci API wajib diisi.',
            'api_key.min' => 'Kunci API terlihat terlalu pendek — pastikan tersalin utuh.',
            'model.in' => 'Model yang dipilih tidak dikenal.',
            'setuju.accepted' => 'Kamu harus menyetujui pemberitahuan pengiriman data sebelum menyalakan asisten AI.',
        ]);

        try {
            Ai::driver($provider)->verify($data['api_key']);
        } catch (AiException $e) {
            // Nothing is saved: an unusable key in the database is worse than
            // no key, because the failure then shows up somewhere else.
            return back()->withErrors(['api_key' => $e->getMessage()]);
        }

        $credential = AiCredential::firstOrNew([
            'user_id' => $user->id,
            'provider' => $provider,
        ]);

        $credential->fillKey($data['api_key']);
        $credential->model = $data['model'];
        $credential->verified_at = now();
        $credential->save();

        if (! $user->hasAiConsent()) {
            $user->update([
                'ai_consented_at' => now(),
                'ai_consent_version' => $user::AI_CONSENT_VERSION,
            ]);
        }

        return back()->with('status', 'Kunci API tersimpan dan sudah diverifikasi.');
    }

    /**
     * Forget the key. The consent stays on the account: it records that the
     * author read the notice, which removing a key does not undo.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->aiCredentials()
            ->where('provider', config('ai.default'))
            ->delete();

        return back()->with('status', 'Kunci API dihapus. Asisten AI tidak bisa dipakai sampai kunci baru dimasukkan.');
    }
}
