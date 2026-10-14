@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'space-y-1 text-xs font-medium text-red-600']) }} role="alert">
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1">
                <span class="material-symbols-outlined text-sm" aria-hidden="true">error</span>
                <span>{{ $message }}</span>
            </li>
        @endforeach
    </ul>
@endif
