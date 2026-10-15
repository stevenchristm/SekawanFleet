<x-app-layout>
    <x-slot name="title">Kendaraan</x-slot>
    <x-slot name="subtitle">Monitoring armada: pemakaian, konsumsi BBM, dan jadwal servis</x-slot>

    @php
        $filterUrl = fn (array $override) => route('vehicles.index', array_filter(array_merge($filters, $override), fn ($v) => $v !== null && $v !== ''));
        $num = fn ($v, $dec = 0) => number_format($v, $dec, ',', '.');
    @endphp

    {{-- Ringkasan --}}
    <section aria-label="Ringkasan armada" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Total kendaraan', $summary['total'], 'local_shipping', 'border-brand-100 bg-brand-50 text-brand-700', null, 'Milik perusahaan & sewa'],
            ['Tersedia', $summary['available'], 'check_circle', 'border-emerald-100 bg-emerald-50 text-emerald-600', null, 'Bisa dipesan'],
            ['Servis terlambat', $summary['overdue'], 'error', 'border-red-100 bg-red-50 text-red-600', 'overdue', 'Lewat tanggal, belum dikerjakan'],
            ['Servis 7 hari ke depan', $summary['soon'], 'build_circle', 'border-amber-100 bg-amber-50 text-amber-600', 'soon', 'Siapkan kendaraan pengganti'],
        ] as [$label, $value, $icon, $tone, $filter, $hint])
            @php $active = $filter && ($filters['service'] ?? null) === $filter; @endphp
            <a @if ($filter) href="{{ $filterUrl(['service' => $active ? null : $filter]) }}" @endif
               @class(['card block p-4 transition-colors sm:p-5', 'hover:border-slate-300' => $filter, 'ring-2 ring-brand-700/40' => $active])
               @if ($active) aria-current="true" @endif>
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate text-xs font-medium text-slate-500 sm:text-sm">{{ $label }}</p>
                        <p class="tabular mt-1.5 font-mono text-2xl font-bold text-slate-900 sm:text-3xl">{{ $value }}</p>
                    </div>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border {{ $tone }}">
                        <span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span>
                    </span>
                </div>
                <p class="mt-2 truncate text-[11px] text-slate-500">{{ $filter ? ($active ? 'Klik untuk hapus filter' : 'Klik untuk menyaring') : $hint }}</p>
            </a>
        @endforeach
    </section>

    {{-- Filter --}}
    <section class="card p-5">
        <form method="GET" class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.4fr)_repeat(3,minmax(0,1fr))_auto]">
            @if (! empty($filters['service']))<input type="hidden" name="service" value="{{ $filters['service'] }}">@endif
            <div>
                <label for="q" class="form-label">Pencarian</label>
                <div class="relative">
                    <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400" aria-hidden="true">search</span>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Plat nomor atau merek" class="form-input pl-10">
                </div>
            </div>
            <div>
                <label for="location_id" class="form-label">Lokasi pool</label>
                <select id="location_id" name="location_id" class="form-input">
                    <option value="">Semua lokasi</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) ($filters['location_id'] ?? '') === (string) $location->id)>{{ $location->name }}</option>
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
                <label for="ownership" class="form-label">Kepemilikan</label>
                <select id="ownership" name="ownership" class="form-input">
                    <option value="">Semua</option>
                    @foreach ($ownerships as $own)
                        <option value="{{ $own->value }}" @selected(($filters['ownership'] ?? '') === $own->value)>{{ $own->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn-primary h-[38px] flex-1"><span class="material-symbols-outlined text-lg" aria-hidden="true">filter_alt</span>Filter</button>
                <a href="{{ route('vehicles.index') }}" class="btn-secondary h-[38px] px-3">Reset</a>
            </div>
        </form>
    </section>

    {{-- Daftar --}}
    <section class="card overflow-hidden">
        <div class="card-header">
            <div>
                <h3 class="card-title">Daftar Kendaraan</h3>
                <p class="card-subtitle">Odometer, jarak, dan BBM dihitung dari pencatatan pengembalian kendaraan dan servis.</p>
            </div>
            @if (! empty($filters['service']))
                <a href="{{ $filterUrl(['service' => null]) }}" class="inline-flex items-center gap-1 rounded-full border border-brand-200 bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-800 hover:bg-brand-100">
                    Servis {{ $filters['service'] === 'overdue' ? 'terlambat' : '7 hari ke depan' }}
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">close</span><span class="sr-only">Hapus filter</span>
                </a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr><th>Kendaraan</th><th>Lokasi</th><th>Status</th><th class="text-right">Odometer</th><th>Pemakaian</th><th>Servis berikutnya</th><th class="text-right">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($vehicles as $vehicle)
                        @php $t = $totals->get($vehicle->id); $next = $vehicle->nextService; @endphp
                        <tr>
                            <td>
                                <a href="{{ route('vehicles.show', $vehicle) }}" class="group flex flex-wrap items-center gap-2">
                                    <span class="rounded bg-sidebar px-2 py-0.5 font-mono text-xs font-bold text-white">{{ $vehicle->plate_number }}</span>
                                    <span class="font-semibold text-slate-900 group-hover:text-brand-700 group-hover:underline">{{ $vehicle->brand_model }}</span>
                                </a>
                                <div class="mt-1 whitespace-nowrap text-[11px] text-slate-500">
                                    {{ $vehicle->type->label() }} &middot; {{ $vehicle->ownership->label() }}@if ($vehicle->rentalCompany) ({{ $vehicle->rentalCompany->name }})@endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap text-slate-700">{{ $vehicle->location->name }}</td>
                            <td><x-status-badge :status="$vehicle->status" /></td>
                            <td class="tabular whitespace-nowrap text-right font-mono text-slate-900">{{ $t?->odometer !== null ? $num($t->odometer).' km' : '-' }}</td>
                            <td class="whitespace-nowrap text-xs">
                                @if ($t && $t->trips)
                                    <div class="font-medium text-slate-900">{{ $t->trips }} perjalanan &middot; {{ $num($t->km) }} km</div>
                                    <div class="text-[11px] text-slate-500">
                                        BBM {{ $num($t->liters, 1) }} L
                                        @if ($t->liters > 0) &middot; {{ $num($t->km / $t->liters, 1) }} km/L @endif
                                    </div>
                                @else
                                    <span class="text-slate-400">Belum ada pemakaian</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-xs">
                                @if ($next)
                                    <div class="flex items-center gap-1.5 font-medium text-slate-900">
                                        <span class="material-symbols-outlined text-base text-slate-400" aria-hidden="true">{{ $next->type->icon() }}</span>{{ $next->type->label() }}
                                    </div>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="tabular font-mono text-[11px] text-slate-600">{{ $next->scheduled_date->format('d/m/Y') }}</span>
                                        <x-service-due :service="$next" />
                                    </div>
                                @else
                                    <span class="text-slate-400">Belum dijadwalkan</span>
                                    @if ($vehicle->lastService)
                                        <div class="text-[11px] text-slate-500">Terakhir {{ $vehicle->lastService->completed_date->format('d/m/Y') }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('vehicles.show', $vehicle) }}" class="btn-sm btn-secondary" title="Detail {{ $vehicle->plate_number }}">
                                    <span class="material-symbols-outlined text-[15px]" aria-hidden="true">visibility</span><span class="sr-only">Detail {{ $vehicle->plate_number }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-12 text-center text-slate-500">Tidak ada kendaraan yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($vehicles->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $vehicles->links() }}</div>
        @endif
    </section>
</x-app-layout>
