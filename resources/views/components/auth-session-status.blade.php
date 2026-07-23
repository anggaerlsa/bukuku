@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'panel border-l-4 border-success px-3 py-2 text-sm text-success font-semibold']) }}>
        {{ $status }}
    </div>
@endif
