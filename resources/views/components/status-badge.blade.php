@props(['status', 'size' => 'sm'])

{{-- $status: enum dengan label() dan color() (BookingStatus / ApprovalStatus) --}}
@php
    [$pill, $dot] = match ($status->color()) {
        'yellow' => ['bg-amber-100 text-amber-800 border-amber-200', 'bg-amber-500'],
        'green' => ['bg-green-100 text-green-800 border-green-200', 'bg-green-500'],
        'red' => ['bg-red-100 text-red-800 border-red-200', 'bg-red-500'],
        'blue' => ['bg-blue-100 text-blue-800 border-blue-200', 'bg-blue-500'],
        default => ['bg-slate-100 text-slate-600 border-slate-200', 'bg-slate-400'],
    };
    $sizing = $size === 'lg' ? 'px-3.5 py-1.5 text-sm' : 'px-2.5 py-0.5 text-xs';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border font-medium {$pill} {$sizing}"]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}" aria-hidden="true"></span>
    {{ $status->label() }}
</span>
