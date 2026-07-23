@props(['world', 'name', 'label', 'options' => [], 'selected' => null, 'hint' => null])

{{--
    Picker for a location that lives in one of the five tier tables. Options are
    grouped per tier and carry a `tier:id` token as their value.
--}}
<div>
    <x-input-label :for="$name" :value="$label" />

    @if (empty($options))
        <div class="input mt-1 flex items-center bg-surface-sunken text-sm text-ink-light">
            Belum ada lokasi di dunia ini.
        </div>
        <p class="text-xs text-ink-light mt-1">
            <a href="{{ route('locations.create', $world) }}" class="underline hover:text-accent-dark">Buat lokasi</a>
            terlebih dahulu agar bisa ditautkan.
        </p>
    @else
        <select id="{{ $name }}" name="{{ $name }}" class="select mt-1">
            <option value="">— tidak ditentukan —</option>
            @foreach ($options as $tier => $items)
                <optgroup label="{{ \App\Support\Hierarchy::label($tier) }}">
                    @foreach ($items as $option)
                        <option value="{{ $option['value'] }}" @selected($selected === $option['value'])>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @if ($hint)
            <p class="text-xs text-ink-light mt-1">{{ $hint }}</p>
        @endif
    @endif

    <x-input-error :messages="$errors->get($name)" />
</div>
