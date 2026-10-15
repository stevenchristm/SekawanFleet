<x-app-layout>
    @php
        $from = \Illuminate\Support\Carbon::parse($filters['from']);
        $to = \Illuminate\Support\Carbon::parse($filters['to']);
        $exportQuery = array_filter($filters, fn ($v) => $v !== null && $v !== '');
        $tile = [
            'pending' => ['border-amber-200/80', 'text-amber-700', 'text-amber-600', 'bg-amber-500', 'Proses review approver'],
            'approved' => ['border-teal-200/80', 'text-teal-700', 'text-teal-600', 'bg-teal-500', 'Siap digunakan'],
            'rejected' => ['border-rose-200/80', 'text-rose-700', 'text-rose-600', 'bg-rose-500', 'Tidak disetujui'],
            'completed' => ['border-emerald-200/80', 'text-emerald-700', 'text-emerald-600', 'bg-emerald-500', 'Kendaraan sudah kembali'],
            'cancelled' => ['border-slate-200', 'text-slate-500', 'text-slate-500', 'bg-slate-400', 'Dibatalkan admin'],
        ];
    @endphp

    <x-slot name="title">Laporan Pemesanan</x-slot>
    <x-slot name="subtitle">Periode {{ $from->format('d/m/Y') }} s/d {{ $to->format('d/m/Y') }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('reports.export', $exportQuery) }}" class="btn bg-emerald-600 text-white shadow-sm hover:bg-emerald-700 focus:ring-emerald-200">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">table_view</span>Export Excel
        </a>
    </x-slot>

    {{-- Filter --}}
    <section class="card p-5">
        <form method="GET" class="grid grid-cols-1 items-end gap-4 md:grid-cols-2 lg:grid-cols-[repeat(5,minmax(0,1fr))_auto]">
            <div>
                <label for="from" class="form-label">Dari tanggal</label>
                <input id="from" name="from" type="date" value="{{ $filters['from'] }}" required class="form-input">
            </div>
            <div>
                <label for="to" class="form-label">Sampai tanggal</label>
                <input id="to" name="to" type="date" value="{{ $filters['to'] }}" required class="form-input">
            </div>
            <div>
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-input">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="type" class="form-label">Jenis angkutan</label>
                <select id="type" name="type" class="form-input">
                    <option value="">Semua jenis</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="location_id" class="form-label">Lokasi kendaraan</label>
                <select id="location_id" name="location_id" class="form-input">
                    <option value="">Semua lokasi</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) ($filters['location_id'] ?? '') === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn-primary h-[38px] flex-1"><span class="material-symbols-outlined text-lg" aria-hidden="true">filter_alt</span>Tampilkan</button>
                <a href="{{ route('reports.index') }}" class="btn-secondary h-[38px] px-3">Reset</a>
            </div>
        </form>
        <x-input-error :messages="$errors->all()" class="mt-3" />
    </section>

    {{-- Ringkasan --}}
    <section aria-label="Ringkasan laporan" class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-7">
        <div class="card flex flex-col justify-between p-4">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total</span>
                <span class="material-symbols-outlined text-slate-400" aria-hidden="true">assignment</span>
            </div>
            <div class="mt-2.5">
                <div class="tabular text-2xl font-bold text-slate-900">{{ $summary['total'] }}</div>
                <div class="mt-0.5 truncate text-[11px] text-slate-500">Total pemesanan</div>
            </div>
        </div>
        @foreach ($statuses as $status)
            @php [$border, $labelTone, $valueTone, $dot, $hint] = $tile[$status->value]; @endphp
            <div class="card flex flex-col justify-between p-4 {{ $border }}">
                <div class="flex items-center justify-between">
                    <span class="truncate text-[11px] font-semibold uppercase tracking-wider {{ $labelTone }}">{{ $status->label() }}</span>
                    <span class="h-2 w-2 shrink-0 rounded-full {{ $dot }}"></span>
                </div>
                <div class="mt-2.5">
                    <div class="tabular text-2xl font-bold {{ $valueTone }}">{{ $summary['by_status'][$status->value] }}</div>
                    <div class="mt-0.5 truncate text-[11px] text-slate-500">{{ $hint }}</div>
                </div>
            </div>
        @endforeach
        <div class="col-span-2 flex flex-col justify-between rounded-xl bg-gradient-to-br from-brand-700 to-[#004D47] p-4 text-white shadow-sm sm:col-span-3 lg:col-span-1">
            <div class="flex items-center justify-between text-brand-100">
                <span class="text-[11px] font-semibold uppercase tracking-wider">Pemakaian</span>
                <span class="material-symbols-outlined text-lg" aria-hidden="true">speed</span>
            </div>
            <div class="mt-2.5">
                <div class="tabular text-2xl font-bold">{{ number_format($summary['hours'], 1, ',', '.') }} <span class="text-sm font-normal">jam</span></div>
                <div class="tabular mt-0.5 text-[11px] text-brand-100">{{ number_format($summary['km'], 0, ',', '.') }} km &middot; {{ number_format($summary['liters'], 1, ',', '.') }} L</div>
            </div>
        </div>
    </section>

    {{-- Preview --}}
    <section class="card overflow-hidden">
        <div class="card-header">
            <div>
                <h3 class="card-title">Pratinjau Data Pemesanan</h3>
                <p class="card-subtitle">Isi tabel ini sama dengan file Excel yang diunduh (sheet "Pemesanan" + "Ringkasan").</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr><th>Kode</th><th>Pemohon / Tujuan</th><th>Kendaraan / Driver</th><th>Jadwal</th><th>Persetujuan</th><th class="text-center">Status</th><th class="text-right">Aksi</th></tr>
                </thead>
                <tbody class="text-xs">
                    @forelse ($bookings as $booking)
                        <tr>
                            <td class="tabular whitespace-nowrap font-mono font-semibold text-brand-700">{{ $booking->code }}</td>
                            <td>
                                <div class="font-semibold text-slate-900">{{ $booking->requester_name }}</div>
                                <div class="mt-0.5 flex items-center gap-1 text-[11px] text-slate-500"><span class="material-symbols-outlined text-[13px]" aria-hidden="true">location_on</span>{{ $booking->destination }}</div>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="font-mono font-bold text-slate-900">{{ $booking->vehicle->plate_number }}</div>
                                <div class="mt-0.5 text-[11px] text-slate-500">{{ $booking->vehicle->type->label() }} &middot; {{ $booking->vehicle->ownership->label() }}</div>
                                <div class="mt-0.5 text-[11px] text-slate-700">Driver: <span class="font-medium">{{ $booking->driver->name }}</span></div>
                            </td>
                            <td class="tabular whitespace-nowrap">
                                <div class="font-medium text-slate-900">{{ $booking->start_at->format('d/m/Y H:i') }}</div>
                                <div class="text-[11px] text-slate-500">s/d {{ $booking->end_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <div class="space-y-1">
                                    @foreach ($booking->approvals as $approval)
                                        @php [$icon, $tone] = match ($approval->status->value) {
                                            'approved' => ['check_circle', 'text-emerald-700'], 'rejected' => ['cancel', 'text-red-600'],
                                            'pending' => ['pending', 'text-amber-700'], default => ['remove', 'text-slate-400'],
                                        }; @endphp
                                        <div class="flex items-center gap-1.5 text-[11px] {{ $tone }}">
                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">{{ $icon }}</span>
                                            <span>L{{ $approval->level }} {{ $approval->approver->name }}: {{ $approval->status->label() }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="whitespace-nowrap text-center"><x-status-badge :status="$booking->status" /></td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('bookings.show', $booking) }}" class="btn-sm btn-secondary" title="Detail {{ $booking->code }}"><span class="material-symbols-outlined text-[15px]" aria-hidden="true">visibility</span><span class="sr-only">Detail {{ $booking->code }}</span></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-12 text-center text-slate-500">Tidak ada pemesanan pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $bookings->links() }}
    </section>
</x-app-layout>
