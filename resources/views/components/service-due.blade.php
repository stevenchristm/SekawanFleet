@props(['service'])

{{-- Penanda jatuh tempo servis terjadwal: terlambat / segera / terjadwal --}}
@php
    $state = $service?->dueState();
    $days = $service ? (int) today()->diffInDays($service->scheduled_date, false) : null;
    [$tone, $icon, $text] = match ($state) {
        'overdue' => ['border-red-200 bg-red-50 text-red-700', 'error', 'Terlambat '.abs($days).' hari'],
        'soon' => ['border-amber-200 bg-amber-50 text-amber-800', 'schedule', $days === 0 ? 'Hari ini' : $days.' hari lagi'],
        'planned' => ['border-slate-200 bg-slate-50 text-slate-600', 'event', $days.' hari lagi'],
        default => [null, null, null],
    };
@endphp

@if ($state)
    <span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 whitespace-nowrap rounded-full border px-2 py-0.5 text-[11px] font-semibold {$tone}"]) }}>
        <span class="material-symbols-outlined text-[13px]" aria-hidden="true">{{ $icon }}</span>{{ $text }}
    </span>
@endif
