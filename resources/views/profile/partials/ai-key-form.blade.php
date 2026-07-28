@php
    $provider = config('ai.default');
    $providerLabel = config("ai.providers.{$provider}.label", ucfirst($provider));
    $docsUrl = config("ai.providers.{$provider}.docs_url");
    $credential = $user->aiCredentialFor($provider);
    $models = \App\Ai\Ai::models($provider);
@endphp

<section>
    <header>
        <h2 class="font-display text-lg text-ink">Asisten AI</h2>
        <p class="mt-1 text-sm text-ink-light">
            Pasang kunci API {{ $providerLabel }} milikmu sendiri untuk bertanya kepada asisten tentang
            satu novel. Pemakaiannya ditagih ke akun {{ $providerLabel }}-mu, bukan ke aplikasi ini.
        </p>
    </header>

    @if (! \App\Ai\Ai::enabled())
        <p class="mt-4 text-sm text-ink-light panel border-l-4 border-line px-4 py-3">
            Fitur asisten AI belum diaktifkan di server ini (<code>AI_ENABLED</code>).
        </p>
    @endif

    @if ($credential)
        <div class="mt-5 panel bg-surface-sunken px-4 py-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
            <span class="text-ink-light">Kunci tersimpan</span>
            <code class="text-ink font-mono">{{ $credential->masked() }}</code>
            @if ($credential->isVerified())
                <span class="badge-success">Terverifikasi</span>
            @else
                <span class="badge-muted">Belum diverifikasi</span>
            @endif
            <span class="text-ink-light">{{ $credential->modelLabel() }}</span>

            <form method="post" action="{{ route('profile.ai.destroy') }}" class="ms-auto"
                  onsubmit="return confirm('Hapus kunci API? Asisten AI tidak bisa dipakai sampai kunci baru dimasukkan.')">
                @csrf
                @method('delete')
                <button type="submit" class="btn-outline btn-sm text-danger">Hapus kunci</button>
            </form>
        </div>
    @endif

    <form method="post" action="{{ route('profile.ai.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        <div>
            <x-input-label for="api_key" value="Kunci API {{ $providerLabel }}" />
            {{-- type=password so the key is not left on screen, and never
                 prefilled: the stored key is not sent to the browser at all. --}}
            <x-text-input id="api_key" name="api_key" type="password" class="mt-1 block w-full font-mono"
                          autocomplete="off" spellcheck="false"
                          placeholder="{{ $credential ? 'Isi untuk mengganti kunci' : 'sk-…' }}" />
            <x-input-error :messages="$errors->get('api_key')" class="mt-2" />
            @if ($docsUrl)
                <p class="mt-1.5 text-xs text-ink-light">
                    Buat kunci di <a href="{{ $docsUrl }}" target="_blank" rel="noopener noreferrer"
                                     class="text-accent-dark underline">dasbor {{ $providerLabel }}</a>.
                </p>
            @endif
        </div>

        <div>
            <x-input-label for="model" value="Model" />
            <select id="model" name="model" class="select mt-1">
                @foreach ($models as $id => $label)
                    <option value="{{ $id }}" @selected(old('model', $credential?->model ?? \App\Ai\Ai::defaultModel($provider)) === $id)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('model')" class="mt-2" />
        </div>

        @if (! $user->hasAiConsent())
            {{-- The disclosure. Deliberately spells out that the manuscript
                 goes too, because that is the part an author would not assume. --}}
            <div class="panel border-l-4 border-accent px-4 py-3 space-y-2 text-sm text-ink">
                <p class="font-medium">Yang dikirim ke {{ $providerLabel }}</p>
                <p class="text-ink-light">
                    Setiap kali kamu bertanya, isi novel yang sedang dibuka dikirim ke server
                    {{ $providerLabel }}: dunia, karakter, lokasi, organisasi, artikel lore,
                    <strong class="text-ink">dan isi naskah babnya</strong> — termasuk bagian yang belum
                    kamu terbitkan.
                </p>
                <p class="text-ink-light">
                    Data itu keluar dari server ini dan selanjutnya tunduk pada kebijakan
                    {{ $providerLabel }}, bukan kebijakan aplikasi ini. Asisten hanya menerima
                    <strong class="text-ink">satu novel per percakapan</strong>; ia tidak pernah dikirimi
                    novelmu yang lain, apalagi novel penulis lain.
                </p>

                <label for="setuju" class="flex items-start gap-2.5 pt-1 cursor-pointer">
                    <input type="checkbox" id="setuju" name="setuju" value="1"
                           class="mt-0.5 rounded border-line text-accent focus:ring-accent/30" @checked(old('setuju')) />
                    <span class="text-ink">Aku sudah membaca dan menyetujui pengiriman data di atas.</span>
                </label>
                <x-input-error :messages="$errors->get('setuju')" class="mt-1" />
            </div>
        @else
            <p class="text-xs text-ink-light">
                Kamu menyetujui pemberitahuan pengiriman data pada
                {{ $user->ai_consented_at->translatedFormat('j F Y, H:i') }}.
            </p>
        @endif

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary">
                {{ $credential ? 'Ganti kunci' : 'Simpan kunci' }}
            </button>
            <span class="text-xs text-ink-light">Kunci diuji ke {{ $providerLabel }} dulu sebelum disimpan.</span>
        </div>
    </form>
</section>
