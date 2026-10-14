<x-app-layout>
    <x-slot name="title">Persetujuan Saya</x-slot>
    <x-slot name="subtitle">Tinjau dan proses permohonan kendaraan yang menunggu keputusan Anda</x-slot>

    @php
        $chip = fn (\App\Enums\ApprovalStatus $s, bool $mine) => match (true) {
            $mine && $s === \App\Enums\ApprovalStatus::Pending => 'border-amber-300 bg-amber-50 text-amber-900',
            $s === \App\Enums\ApprovalStatus::Approved => 'border-green-200 bg-green-50 text-green-800',
            $s === \App\Enums\ApprovalStatus::Rejected => 'border-red-200 bg-red-50 text-red-800',
            $s === \App\Enums\ApprovalStatus::Pending => 'border-amber-200 bg-amber-50 text-amber-800',
            default => 'border-slate-200 bg-slate-100 text-slate-500',
        };
    @endphp

    <x-input-error :messages="$errors->get('approval')" />

    {{-- Tab & pencarian --}}
    <div class="card space-y-4 p-4">
        <nav class="flex items-center gap-4 border-b border-slate-100" aria-label="Tab persetujuan">
            @foreach (['pending' => 'Menunggu Saya', 'history' => 'Riwayat Persetujuan'] as $key => $label)
                <a href="{{ route('approvals.index', array_filter(['tab' => $key, 'q' => $term ?: null])) }}"
                   @class([
                       '-mb-px flex items-center gap-2 border-b-2 pb-2.5 text-sm transition-colors',
                       'border-brand-700 font-semibold text-brand-800' => $tab === $key,
                       'border-transparent font-medium text-slate-500 hover:text-slate-800' => $tab !== $key,
                   ]) @if ($tab === $key) aria-current="page" @endif>
                    {{ $label }}
                    <span @class([
                        'tabular rounded-full px-2 py-0.5 text-xs leading-none',
                        'bg-red-500 font-bold text-white' => $key === 'pending' && $counts[$key] > 0,
                        'bg-slate-100 font-medium text-slate-600' => ! ($key === 'pending' && $counts[$key] > 0),
                    ])>{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </nav>

        <form method="GET" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="relative min-w-[240px] max-w-md flex-1">
                <label for="q" class="sr-only">Cari</label>
                <span class="material-symbols-outlined pointer-events-none absolute inset-y-0 left-3 my-auto text-lg text-slate-400" aria-hidden="true">search</span>
                <input id="q" name="q" type="search" value="{{ $term }}" placeholder="Cari pemohon, kode booking, tujuan, plat..." class="form-input pl-9">
            </div>
            <button type="submit" class="btn-secondary h-[38px]">Cari</button>
            @if ($term)<a href="{{ route('approvals.index', ['tab' => $tab]) }}" class="text-xs text-slate-500 hover:text-slate-800">Reset</a>@endif
        </form>
    </div>

    {{-- Daftar --}}
    <div class="space-y-4">
        @forelse ($approvals as $approval)
            @php $booking = $approval->booking; $isMine = $tab === 'pending'; @endphp
            <article @class(['card overflow-hidden', 'ring-1 ring-brand-600/30 border-brand-600/50' => $isMine && $loop->first])>
                <div class="p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-4">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-3">
                                <a href="{{ route('bookings.show', $booking) }}" class="code-chip tracking-wider hover:bg-brand-100">{{ $booking->code }}</a>
                                <span class="flex flex-wrap items-center gap-1.5 text-base font-bold text-slate-900">
                                    {{ $booking->requester_name }}
                                    <span class="material-symbols-outlined text-lg text-slate-400" aria-hidden="true">arrow_forward</span>
                                    <span class="text-brand-800">{{ $booking->destination }}</span>
                                </span>
                            </div>
                            <p class="flex items-center gap-1.5 pl-0.5 text-xs font-medium text-slate-500">
                                <span class="material-symbols-outlined text-base text-slate-400" aria-hidden="true">assignment</span>Keperluan: {{ $booking->purpose }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="rounded-md border border-slate-200 bg-slate-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-600">Level {{ $approval->level }} dari {{ $booking->approvals->count() }}</span>
                            <x-status-badge :status="$isMine ? $booking->status : $approval->status" />
                        </div>
                    </div>

                    <div class="-mx-5 my-2 grid grid-cols-1 gap-4 border-y border-slate-100/80 bg-slate-50/70 px-5 py-4 md:grid-cols-3">
                        @foreach ([
                            ['calendar_month', 'Jadwal', $booking->start_at->format('d/m/Y H:i').' – '.$booking->end_at->format('d/m/Y H:i'), 'Durasi: '.rtrim(rtrim(number_format($booking->start_at->diffInMinutes($booking->end_at) / 60, 1, ',', ''), '0'), ',').' jam'],
                            ['directions_car', 'Kendaraan', $booking->vehicle->brand_model, 'Plat: '.$booking->vehicle->plate_number],
                            ['badge', 'Driver', $booking->driver->name, $booking->driver->phone],
                        ] as [$icon, $label, $value, $hint])
                            <div class="flex items-start gap-2.5">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600"><span class="material-symbols-outlined text-lg" aria-hidden="true">{{ $icon }}</span></span>
                                <div>
                                    <span class="block text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</span>
                                    <span class="tabular block text-xs font-semibold text-slate-800">{{ $value }}</span>
                                    @if ($hint)<span class="block font-mono text-[11px] text-slate-500">{{ $hint }}</span>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center gap-2 py-2.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Alur:</span>
                        @foreach ($booking->approvals as $step)
                            @php $mine = $step->is($approval); @endphp
                            @if (! $loop->first)<span class="material-symbols-outlined text-base text-slate-300" aria-hidden="true">chevron_right</span>@endif
                            <span class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-medium {{ $chip($step->status, $mine) }}">
                                <strong class="font-semibold">L{{ $step->level }} {{ $step->approver->name }}:</strong>
                                {{ $mine && $step->status === \App\Enums\ApprovalStatus::Pending ? 'Menunggu persetujuan Anda' : $step->status->label() }}
                            </span>
                        @endforeach
                    </div>

                    <div class="mt-2 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3.5">
                        <span class="text-xs text-slate-400">
                            @if ($isMine) Diajukan: {{ $booking->created_at->format('d/m/Y H:i') }} @else Diproses: {{ $approval->acted_at?->format('d/m/Y H:i') }} @endif
                        </span>
                        @if ($isMine)
                            @include('approvals._actions', ['approval' => $approval])
                        @elseif ($approval->note)
                            <p class="rounded bg-slate-50 px-2.5 py-1.5 text-xs italic text-slate-700">Catatan Anda: &ldquo;{{ $approval->note }}&rdquo;</p>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white/60 p-8 text-center">
                <span class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-600">
                    <span class="material-symbols-outlined text-2xl" aria-hidden="true">check_circle</span>
                </span>
                <h4 class="font-semibold text-slate-700">{{ $tab === 'pending' ? 'Tidak ada pemesanan yang menunggu persetujuan Anda.' : 'Belum ada riwayat persetujuan.' }}</h4>
                @if ($tab === 'pending')<p class="mt-0.5 text-xs text-slate-500">Semua permohonan sudah Anda tinjau.</p>@endif
            </div>
        @endforelse
    </div>

    @if ($approvals->hasPages())
        <div class="card overflow-hidden">{{ $approvals->links() }}</div>
    @endif
</x-app-layout>
