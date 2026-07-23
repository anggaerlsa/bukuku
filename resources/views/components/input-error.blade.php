@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'mt-1 text-sm text-danger font-semibold space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
