<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('novels.show', $novel) }}" class="text-sm text-ink hover:text-accent-dark">← {{ $novel->title }}</a>
                <h1 class="font-display text-2xl text-ink">🤖 Asisten AI</h1>
            </div>

            <form method="POST" action="{{ route('ai.toggle', $novel) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="ai_enabled" value="{{ $novel->ai_enabled ? 0 : 1 }}">
                <button class="{{ $novel->ai_enabled ? 'btn-outline' : 'btn-primary' }} btn-sm">
                    {{ $novel->ai_enabled ? 'Matikan untuk novel ini' : 'Nyalakan untuk novel ini' }}
                </button>
            </form>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid lg:grid-cols-[15rem_1fr] gap-6 items-start">
            {{-- Threads. All of them belong to this novel and this author; there
                 is no "all conversations" view anywhere, by design. --}}
            <aside class="panel p-3 space-y-1">
                <a href="{{ route('ai.index', $novel) }}"
                   class="block px-3 py-2 rounded-lg text-sm {{ $conversation === null ? 'bg-accent-soft text-accent-dark' : 'text-ink hover:bg-surface-muted' }}">
                    + Percakapan baru
                </a>

                @forelse ($conversations as $thread)
                    <div class="group flex items-center gap-1">
                        <a href="{{ route('ai.show', [$novel, $thread]) }}"
                           class="flex-1 min-w-0 px-3 py-2 rounded-lg text-sm truncate {{ $conversation?->id === $thread->id ? 'bg-accent-soft text-accent-dark' : 'text-ink hover:bg-surface-muted' }}"
                           title="{{ $thread->displayTitle() }}">
                            {{ $thread->displayTitle() }}
                        </a>
                        <form method="POST" action="{{ route('ai.destroy', [$novel, $thread]) }}"
                              onsubmit="return confirm('Hapus percakapan ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="px-2 py-1 text-xs text-ink-faint hover:text-danger" title="Hapus">✕</button>
                        </form>
                    </div>
                @empty
                    <p class="px-3 py-2 text-xs text-ink-light">Belum ada percakapan.</p>
                @endforelse
            </aside>

            <section class="space-y-4 min-w-0">
                @if (! $readiness['ready'])
                    {{-- Which step is missing, rather than a bare refusal. --}}
                    <div class="panel border-l-4 border-accent p-5 space-y-3">
                        <h2 class="font-display text-lg text-ink">Asisten belum siap dipakai</h2>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-start gap-2">
                                <span>{{ $readiness['enabled'] ? '✅' : '⛔' }}</span>
                                <span class="text-ink">
                                    Fitur asisten diaktifkan di server
                                    @unless ($readiness['enabled'])
                                        <span class="text-ink-light">— setel <code>AI_ENABLED=true</code> di <code>.env</code>.</span>
                                    @endunless
                                </span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span>{{ $readiness['credential'] ? '✅' : '⛔' }}</span>
                                <span class="text-ink">
                                    Kunci API tersimpan
                                    @unless ($readiness['credential'])
                                        — <a href="{{ route('profile.edit') }}" class="text-accent-dark underline">pasang di Profil</a>.
                                    @endunless
                                </span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span>{{ $readiness['consented'] ? '✅' : '⛔' }}</span>
                                <span class="text-ink">
                                    Persetujuan pengiriman data
                                    @unless ($readiness['consented'])
                                        — <a href="{{ route('profile.edit') }}" class="text-accent-dark underline">baca &amp; setujui di Profil</a>.
                                    @endunless
                                </span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span>{{ $readiness['armed'] ? '✅' : '⛔' }}</span>
                                <span class="text-ink">
                                    Asisten dinyalakan untuk novel ini
                                    @unless ($readiness['armed'])
                                        <span class="text-ink-light">— pakai tombol di kanan atas.</span>
                                    @endunless
                                </span>
                            </li>
                        </ul>
                    </div>
                @else
                    {{-- What the assistant can see this turn. Stated plainly:
                         an answer only means something against a known context. --}}
                    <div class="panel bg-surface-sunken px-4 py-3 text-xs text-ink-light flex flex-wrap gap-x-4 gap-y-1">
                        <span>Konteks: <strong class="text-ink">{{ number_format($dossier->tokens) }}</strong> token (perkiraan)</span>
                        @if ($conversation?->with_manuscript ?? true)
                            <span>Naskah: <strong class="text-ink">{{ $dossier->chaptersIncluded }}/{{ $dossier->chaptersTotal }}</strong> bab</span>
                        @else
                            <span>Naskah: <strong class="text-ink">tidak dikirim</strong> ({{ $dossier->chaptersTotal }} bab dilewati)</span>
                        @endif
                        <span>Model: <strong class="text-ink">{{ $conversation?->model ?: $readiness['credential']->model }}</strong></span>
                    </div>

                    @if ($dossier->wasTrimmed())
                        <div class="panel border-l-4 border-danger px-4 py-3 text-sm text-ink">
                            ⚠️ Novel ini lebih besar dari batas konteks. Naskah terkirim sampai
                            <strong>{{ $dossier->trimmedFrom }}</strong>. Jawaban soal bab setelah itu tidak bisa dipercaya.
                        </div>
                    @endif

                    @if ($messages->count() > $historyLimit)
                        <div class="panel border-l-4 border-line px-4 py-3 text-sm text-ink-light">
                            Percakapan ini sudah {{ $messages->count() }} pesan. Hanya
                            <strong class="text-ink">{{ $historyLimit }}</strong> pesan terakhir yang dikirim ulang
                            sebagai ingatan — yang lebih tua tidak lagi dilihat asisten.
                        </div>
                    @endif

                    {{-- Transcript --}}
                    <div class="space-y-3">
                        @forelse ($messages as $message)
                            <div class="flex {{ $message->isUser() ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[46rem] panel px-4 py-3 {{ $message->isUser() ? 'bg-accent-soft border-accent/25' : '' }}">
                                    <p class="text-[0.65rem] uppercase tracking-wider text-ink-faint mb-1">
                                        {{ $message->isUser() ? 'Kamu' : 'Asisten' }}
                                    </p>
                                    {{-- Plain text on purpose: Markdown is not rendered
                                         anywhere in this app yet, and pretending otherwise
                                         here would be inconsistent. --}}
                                    <div class="text-sm text-ink whitespace-pre-wrap break-words">{{ $message->content }}</div>
                                    @if ($message->tokens_in)
                                        @php
                                            $usage = number_format($message->tokens_in) . ' token masuk';
                                            if ($message->tokens_cached) {
                                                $usage .= ' (' . number_format($message->tokens_cached) . ' dari cache)';
                                            }
                                            $usage .= ' · ' . number_format($message->tokens_out) . ' keluar';
                                        @endphp
                                        <p class="mt-2 text-[0.65rem] text-ink-faint">{{ $usage }}</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="panel p-10 text-center text-ink-light">
                                <p class="font-display text-lg text-ink">Tanyakan apa saja tentang novel ini.</p>
                                <p class="mt-1 text-sm">
                                    Misalnya: “siapa saja yang pernah tinggal di ibu kota?”, “apakah ada dua tokoh
                                    dengan latar yang bertentangan?”, “ringkas peran organisasi ini sampai bab 40”.
                                </p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Asking. The button locks on submit: one send loads the
                         whole novel to the provider, so a double click would be
                         a double charge on the author's own account. --}}
                    <form method="POST" action="{{ route('ai.store', $novel) }}" class="panel p-4 space-y-3"
                          x-data="{ sending: false }" @submit="sending = true">
                        @csrf
                        @if ($conversation)
                            <input type="hidden" name="conversation_id" value="{{ $conversation->id }}">
                        @endif

                        <div>
                            <x-input-label for="question" value="Pertanyaanmu" />
                            <textarea id="question" name="question" rows="3" class="textarea mt-1"
                                      placeholder="Tulis pertanyaan tentang novel ini…"
                                      x-bind:disabled="sending">{{ old('question') }}</textarea>
                            <x-input-error :messages="$errors->get('question')" class="mt-2" />
                        </div>

                        @unless ($conversation)
                            <label for="with_manuscript" class="flex items-start gap-2.5 text-sm cursor-pointer">
                                <input type="checkbox" id="with_manuscript" name="with_manuscript" value="1" checked
                                       class="mt-0.5 rounded border-line text-accent focus:ring-accent/30">
                                <span class="text-ink">
                                    Sertakan naskah bab
                                    <span class="text-ink-light">
                                        — tanpa ini asisten hanya melihat lore, karakter, lokasi, dan organisasi.
                                        Pilihan ini terkunci untuk percakapan ini setelah pesan pertama.
                                    </span>
                                </span>
                            </label>
                        @endunless

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit" class="btn-primary" x-bind:disabled="sending">
                                <span x-show="! sending">Kirim</span>
                                <span x-show="sending" x-cloak>Menunggu jawaban…</span>
                            </button>
                            <span class="text-xs text-ink-light">
                                Mengirim seluruh konteks novel — biasanya perlu belasan detik. Jangan tutup halaman.
                            </span>
                        </div>
                    </form>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
