@props(['fields', 'values' => [], 'tierAware' => false])

{{--
    Inputs for the world's own attributes. On the location form the tier is
    still changeable, so tier-specific fields are toggled by Alpine against the
    form's `tier` state; values for tiers that do not apply are ignored on save.
--}}
@if ($fields->isNotEmpty())
    <div class="space-y-5 border-t border-line/40 pt-5">
        <div>
            <p class="label">Atribut Dunia</p>
            <p class="text-xs text-ink-light">Kolom khusus yang kamu tetapkan sendiri untuk dunia ini.</p>
        </div>

        @foreach ($fields as $field)
            @php($current = old("custom.{$field->id}", $values[$field->id] ?? null))
            <div @if ($tierAware && $field->applies_to !== 'location') x-show="tier === '{{ $field->applies_to }}'" x-cloak @endif>
                <x-input-label :for="'custom-' . $field->id" :value="$field->name" />

                @if ($field->type === 'textarea')
                    <textarea id="custom-{{ $field->id }}" name="custom[{{ $field->id }}]" rows="3" class="textarea mt-1">{{ $current }}</textarea>
                @elseif ($field->type === 'number')
                    <x-text-input :id="'custom-' . $field->id" :name="'custom[' . $field->id . ']'" type="number" step="any" class="mt-1" :value="$current" />
                @elseif ($field->type === 'select')
                    <select id="custom-{{ $field->id }}" name="custom[{{ $field->id }}]" class="select mt-1">
                        <option value="">—</option>
                        @foreach ($field->optionList() as $option)
                            <option value="{{ $option }}" @selected($current === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                @elseif ($field->type === 'boolean')
                    <select id="custom-{{ $field->id }}" name="custom[{{ $field->id }}]" class="select mt-1">
                        <option value="">—</option>
                        <option value="1" @selected($current === '1')>Ya</option>
                        <option value="0" @selected($current === '0')>Tidak</option>
                    </select>
                @else
                    <x-text-input :id="'custom-' . $field->id" :name="'custom[' . $field->id . ']'" type="text" class="mt-1" :value="$current" />
                @endif

                @if ($field->hint)
                    <p class="text-xs text-ink-light mt-1">{{ $field->hint }}</p>
                @endif
                @if ($tierAware && $field->applies_to !== 'location')
                    <p class="text-xs text-ink-light mt-1">Hanya untuk tingkat {{ \App\Support\Hierarchy::label($field->applies_to) }}.</p>
                @endif
                <x-input-error :messages="$errors->get('custom.' . $field->id)" />
            </div>
        @endforeach
    </div>
@endif
