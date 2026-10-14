<x-app-layout>
    <x-slot name="title">Pemesanan Kendaraan</x-slot>
    <x-slot name="subtitle">Kelola semua pemesanan kendaraan tambang &amp; operasional</x-slot>
    <x-slot name="actions">
        <a href="{{ route('bookings.create') }}" class="btn-primary">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">add</span>Buat Pemesanan
        </a>
    </x-slot>

    @php
        $tabUrl = fn ($status) => route('bookings.index', array_filter(array_merge($filters, ['status' => $status, 'page' => null]), fn ($v) => $v !== null && $v !== ''));
        $active = $filters['status'] ?? null;
        $tabTone = [
            'pending' => 'bg-amber-100 text-amber-700', 'approved' => 'bg-green-100 text-green-700',
            'rejected' => 'bg-red-100 text-red-700', 'completed' => 'bg-blue-100 text-blue-700',
            'cancelled' => 'bg-slate-100 text-slate-600',
        ];
    @endphp

    {{-- Tab status --}}
    <nav aria-label="Filter status" class="flex overflow-x-auto border-b border-slate-200 pb-2">
        <div class="flex items-center gap-2">
            <a href="{{ $tabUrl(null) }}" @class([
                'flex items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-1.5 text-xs',
                'border border-slate-300 bg-white font-semibold text-brand-700 shadow-sm' => ! $active,
                'font-medium text-slate-600 hover:bg-slate-100' => $active,
            ])>Semua <span class="tabular rounded-full bg-brand-100 px-1.5 py-0.5 text-[11px] text-brand-700">{{ $counts->sum() }}</span></a>
            @foreach ($statuses as $status)
                <a href="{{ $tabUrl($status->value) }}" @class([
                    'flex items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-1.5 text-xs',
                    'border border-slate-300 bg-white font-semibold text-brand-700 shadow-sm' => $active === $status->value,
                    'font-medium text-slate-600 hover:bg-slate-100' => $active !== $status->value,
                ]) @if ($active === $status->value) aria-current="true" @endif>
                    {{ $status->label() }}
                    <span class="tabular rounded-full px-1.5 py-0.5 text-[11px] {{ $tabTone[$status->value] }}">{{ $counts[$status->value] ?? 0 }}</span>
                </a>
            @endforeach
        </div>
    </nav>

    {{-- Filter --}}
    <section class="card p-4">
        <form method="GET" class="grid grid-cols-12 items-end gap-3">
            @if ($active)<input type="hidden" name="status" value="{{ $active }}">@endif
            <div class="col-span-12 lg:col-span-4">
                <label for="q" class="form-label">Pencarian</label>
                <div class="relative">
                    <span class="material-symbols-outlined pointer-events-none absolute inset-y-0 left-3 my-auto text-lg text-slate-400" aria-hidden="true">search</span>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Cari kode, pemohon, tujuan, plat..." class="form-input pl-9">
                </div>
            </div>
            <div class="col-span-12 sm:col-span-4 lg:col-span-3">
                <label for="location_id" class="form-label">Lokasi kendaraan</label>
                <select id="location_id" name="location_id" class="form-input">
                    <option value="">Semua lokasi</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) ($filters['location_id'] ?? '') === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-6 sm:col-span-4 lg:col-span-2">
                <label for="from" class="form-label">Dari tanggal</label>
                <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="form-input">
            </div>
            <div class="col-span-6 sm:col-span-4 lg:col-span-2">
                <label for="to" class="form-label">Sampai tanggal</label>
                <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="form-input">
            </div>
            <div class="col-span-12 flex gap-2 lg:col-span-1">
                <button type="submit" class="btn-primary h-[38px] flex-1 px-3" title="Terapkan filter">
                    <span class="material-symbols-outlined text-lg" aria-hidden="true">filter_alt</span><span class="lg:sr-only">Filter</span>
                </button>
                <a href="{{ route('bookings.index') }}" class="btn-secondary h-[38px] px-2.5" title="Reset filter">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">restart_alt</span><span class="sr-only">Reset</span>
                </a>
            </div>
        </form>
        <x-input-error :messages="$errors->all()" class="mt-3" />
    </section>

    {{-- Tabel --}}
    <section class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-36">Kode</th>
                        <th>Pemohon &amp; Tujuan</th>
                        <th>Kendaraan</th>
                        <th>Driver</th>
                        <th>Jadwal</th>
                        <th>Status Persetujuan</th>
                        <th class="w-24 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td>
                                <span class="code-chip">{{ $booking->code }}</span>
                                <div class="tabular mt-1 text-[11px] text-slate-400">{{ $booking->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <div class="font-semibold leading-tight text-slate-900">{{ $booking->requester_name }}</div>
                                <div class="mt-0.5 flex items-center gap-1 text-xs text-slate-500">
                                    <span class="material-symbols-outlined text-[13px] text-slate-400" aria-hidden="true">pin_drop</span>{{ $booking->destination }}
                                </div>
                            </td>
                            <td>
                                <div class="tabular font-semibold text-slate-900">{{ $booking->vehicle->plate_number }}</div>
                                <div class="text-xs text-slate-500">{{ $booking->vehicle->brand_model }} &middot; {{ $booking->vehicle->location->name }}</div>
                            </td>
                            <td><div class="font-medium text-slate-900">{{ $booking->driver->name }}</div></td>
                            <td class="tabular whitespace-nowrap">
                                <div class="text-xs font-semibold text-slate-900">{{ $booking->start_at->format('d/m/Y H:i') }}</div>
                                <div class="text-xs text-slate-500">s/d {{ $booking->end_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <x-status-badge :status="$booking->status" />
                                @if ($booking->currentApproval)
                                    <div class="mt-1 flex items-center gap-1 text-[11px] text-slate-500">
                                        <span class="material-symbols-outlined text-xs text-amber-600" aria-hidden="true">hourglass_top</span>
                                        L{{ $booking->currentApproval->level }}: {{ $booking->currentApproval->approver->name }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('bookings.show', $booking) }}" class="btn-sm btn border border-brand-700/30 font-semibold text-brand-700 hover:bg-brand-700 hover:text-white">
                                    Detail<span class="material-symbols-outlined text-sm" aria-hidden="true">chevron_right</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center">
                                <span class="material-symbols-outlined text-4xl text-slate-300" aria-hidden="true">event_busy</span>
                                <p class="mt-2 text-slate-500">Belum ada pemesanan yang cocok dengan filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $bookings->links() }}
    </section>
</x-app-layout>
