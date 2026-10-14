@props(['status'])

{{-- $status: enum dengan method label() dan color() (BookingStatus / ApprovalStatus) --}}
@php
    $classes = match ($status->color()) {
        'yellow' => 'bg-yellow-100 text-yellow-800',
        'green' => 'bg-green-100 text-green-800',
        'red' => 'bg-red-100 text-red-800',
        'blue' => 'bg-blue-100 text-blue-800',
        default => 'bg-gray-100 text-gray-700',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {$classes}"]) }}>
    {{ $status->label() }}
</span>
