<x-app-layout>
    <x-slot name="title">Detail Kendaraan</x-slot>
    <x-slot name="subtitle">Kendaraan / {{ $vehicle->plate_number }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('vehicles.index') }}" class="btn-ghost">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">arrow_back</span>Kembali
        </a>
        <button type="button" x-data x-on:click.prevent="$dispatch('open-modal', 'schedule-service')" class="btn-primary">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">add</span>Jadwalkan Servis
        </button>
    </x-slot>

    @php
        $num = fn ($v, $dec = 0) => number_format($v, $dec, ',', '.');
        $odometer = $totals?->odometer;
        $maxKm = max($monthly['km'] ?: [0]) ?: 1;
        $maxL = max($monthly['liters'] ?: [0]) ?: 1;
    @endphp

    <x-input-error :messages="$errors->get('service')" />

    {{-- Ringkasan --}}
    <div class="card flex flex-col justify-between gap-4 p-6 md:flex-row md:items-center">
        <div class="space-y-2">
            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-md bg-sidebar px-2.5 py-1 font-mono text-lg font-bold text-white">{{ $vehicle->plate_number }}</span>
                <h2 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $vehicle->brand_model }}</h2>
            </div>
            <p class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-base" aria-hidden="true">category</span>{{ $vehicle->type->label() }}@if ($vehicle->capacity) &middot; {{ $vehicle->capacity }} {{ $vehicle->type->value === 'cargo' ? 'ton' : 'penumpang' }}@endif</span>
                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-base" aria-hidden="true">badge</span>{{ $vehicle->ownership->label() }}@if ($vehicle->rentalCompany) &mdash; {{ $vehicle->rentalCompany->name }}@endif</span>
                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-base" aria-hidden="true">warehouse</span>{{ $vehicle->location->name }}</span>
            </p>
        </div>
        <x-status-badge :status="$vehicle->status" size="lg" class="self-start md:self-center" />
    </div>

    {{-- KPI --}}
    <section aria-label="Statistik pemakaian" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Odometer terakhir', $odometer !== null ? $num($odometer) : '-', 'km', 'speed', 'border-brand-100 bg-brand-50 text-brand-700'],
            ['Total perjalanan', $totals?->trips ?? 0, 'trip', 'route', 'border-blue-100 bg-blue-50 text-blue-600'],
            ['Jarak tempuh', $num($totals?->km ?? 0), 'km', 'straighten', 'border-violet-100 bg-violet-50 text-violet-600'],
            ['Konsumsi BBM', $num($totals?->liters ?? 0, 1), 'L', 'local_gas_station', 'border-amber-100 bg-amber-50 text-amber-600'],
        ] as [$label, $value, $unit, $icon, $tone])
            <div class="card p-4 sm:p-5">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate text-xs font-medium text-slate-500 sm:text-sm">{{ $label }}</p>
                        <p class="tabular mt-1.5 font-mono text-xl font-bold text-slate-900 sm:text-2xl">{{ $value }} <span class="text-sm font-normal text-slate-400">{{ $unit }}</span></p>
                    </div>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border {{ $tone }}">
                        <span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span>
                    </span>
                </div>
                @if ($label === 'Konsumsi BBM')
                    <p class="mt-2 text-[11px] text-slate-500">
                        {{ ($totals?->liters ?? 0) > 0 ? 'Rasio '.$num($totals->km / $totals->liters, 1).' km/L' : 'Belum ada data BBM' }}
                    </p>
                @endif
            </div>
        @endforeach
    </section>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
        {{-- Kiri: pemakaian --}}
        <div class="min-w-0 space-y-6 lg:col-span-8">
            <section class="card p-6">
                <div class="flex flex-col justify-between gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-center">
                    <div>
                        <h3 class="card-title">Pemakaian 6 Bulan Terakhir</h3>
                        <p class="card-subtitle">Jarak tempuh dan BBM per bulan dari pencatatan pengembalian</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-slate-600">
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-brand-700"></span>Jarak (km)</span>
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-amber-500"></span>BBM (liter)</span>
                    </div>
                </div>
                <table class="mt-4 w-full text-xs">
                    <caption class="sr-only">Jarak dan BBM per bulan</caption>
                    <thead class="sr-only"><tr><th>Bulan</th><th>Jarak</th><th>BBM</th></tr></thead>
                    <tbody>
                        @foreach ($monthly['labels'] as $i => $label)
                            <tr>
                                <th scope="row" class="w-20 py-1.5 pr-3 text-left font-medium text-slate-600">{{ $label }}</th>
                                <td class="py-1.5">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2.5 rounded-sm bg-brand-700" style="width: {{ max(round($monthly['km'][$i] / $maxKm * 100), $monthly['km'][$i] ? 1 : 0) }}%"></div>
                                        <span class="tabular whitespace-nowrap font-mono text-[11px] text-slate-700">{{ $num($monthly['km'][$i]) }} km</span>
                                    </div>
                                    <div class="mt-1 flex items-center gap-2">
                                        <div class="h-2.5 rounded-sm bg-amber-500" style="width: {{ max(round($monthly['liters'][$i] / $maxL * 100), $monthly['liters'][$i] ? 1 : 0) }}%"></div>
                                        <span class="tabular whitespace-nowrap font-mono text-[11px] text-slate-500">{{ $num($monthly['liters'][$i], 1) }} L</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            <section class="card overflow-hidden">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Riwayat Pemakaian</h3>
                        <p class="card-subtitle">Setiap pemesanan yang sudah dicatat pengembaliannya</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr><th>Pemesanan</th><th>Kembali</th><th>Pemohon / Driver</th><th class="text-right">Odometer</th><th class="text-right">Jarak</th><th class="text-right">BBM</th></tr>
                        </thead>
                        <tbody class="text-xs">
                            @forelse ($usages as $usage)
                                <tr>
                                    <td class="whitespace-nowrap"><a href="{{ route('bookings.show', $usage->booking) }}" class="font-mono font-semibold text-brand-700 hover:underline">{{ $usage->booking->code }}</a></td>
                                    <td class="tabular whitespace-nowrap text-slate-700">{{ $usage->returned_at?->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="font-medium text-slate-900">{{ $usage->booking->requester_name }}</div>
                                        <div class="text-[11px] text-slate-500">Driver: {{ $usage->booking->driver->name }}</div>
                                    </td>
                                    <td class="tabular whitespace-nowrap text-right font-mono text-slate-600">{{ $num($usage->start_odometer) }} &rarr; {{ $usage->end_odometer !== null ? $num($usage->end_odometer) : '-' }}</td>
                                    <td class="tabular whitespace-nowrap text-right font-mono font-semibold text-slate-900">{{ $usage->distance !== null ? $num($usage->distance).' km' : '-' }}</td>
                                    <td class="tabular whitespace-nowrap text-right font-mono text-slate-900">{{ $usage->fuel_liters !== null ? $num((float) $usage->fuel_liters, 1).' L' : '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-10 text-center text-slate-500">Belum ada riwayat pemakaian.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($usages->hasPages())
                    <div class="border-t border-slate-100 px-5 py-3">{{ $usages->links() }}</div>
                @endif
            </section>
        </div>

        {{-- Kanan: servis & jadwal --}}
        <div class="min-w-0 space-y-6 lg:col-span-4">
            <section class="card p-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[22px] text-brand-700" aria-hidden="true">build</span>
                        <h3 class="card-title">Jadwal Servis</h3>
                    </div>
                    <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">{{ $scheduled->count() }} terjadwal</span>
                </div>

                <ul class="mt-4 space-y-4">
                    @forelse ($scheduled as $service)
                        @php $reached = $service->odometer_due && $odometer !== null && $odometer >= $service->odometer_due; @endphp
                        <li @class([
                            'space-y-2.5 rounded-xl border p-4',
                            'border-red-200 bg-red-50/60' => $service->dueState() === 'overdue',
                            'border-amber-200 bg-amber-50/60' => $service->dueState() === 'soon',
                            'border-slate-200 bg-slate-50' => $service->dueState() === 'planned',
                        ])>
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-start gap-2">
                                    <span class="material-symbols-outlined mt-0.5 text-lg text-slate-500" aria-hidden="true">{{ $service->type->icon() }}</span>
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">{{ $service->type->label() }}</div>
                                        <div class="tabular font-mono text-xs text-slate-600">{{ $service->scheduled_date->translatedFormat('l, d/m/Y') }}</div>
                                    </div>
                                </div>
                                <x-service-due :service="$service" class="shrink-0" />
                            </div>
                            @if ($service->description)<p class="text-xs text-slate-700">{{ $service->description }}</p>@endif
                            <dl class="space-y-1 text-[11px] text-slate-600">
                                @if ($service->odometer_due)
                                    <div class="flex items-center gap-1.5">
                                        <dt>Target odometer:</dt>
                                        <dd class="tabular font-mono font-semibold text-slate-800">{{ $num($service->odometer_due) }} km</dd>
                                        @if ($reached)<dd class="rounded bg-red-100 px-1.5 font-semibold text-red-700">sudah tercapai</dd>@endif
                                    </div>
                                @endif
                                @if ($service->workshop)<div class="flex gap-1.5"><dt>Bengkel:</dt><dd class="font-medium text-slate-800">{{ $service->workshop }}</dd></div>@endif
                            </dl>
                            <div class="flex gap-2 pt-1">
                                <button type="button" x-data x-on:click.prevent="$dispatch('open-modal', 'complete-service-{{ $service->id }}')" class="btn-success btn-sm flex-1">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">task_alt</span>Tandai Selesai
                                </button>
                                <form method="POST" action="{{ route('services.cancel', $service) }}" onsubmit="return confirm('Batalkan jadwal {{ $service->type->label() }} tanggal {{ $service->scheduled_date->format('d/m/Y') }}?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-danger-outline btn-sm h-full" title="Batalkan jadwal">
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">event_busy</span><span class="sr-only">Batalkan jadwal</span>
                                    </button>
                                </form>
                            </div>
                        </li>

                        {{-- Modal: tandai selesai --}}
                        <x-modal name="complete-service-{{ $service->id }}" :show="$errors->completeService->any() && (int) old('_service_id') === $service->id" maxWidth="lg" focusable>
                            <form method="POST" action="{{ route('services.complete', $service) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="_service_id" value="{{ $service->id }}">
                                <div class="flex items-start justify-between border-b border-slate-100 p-6 pb-4">
                                    <div class="flex items-start gap-3.5">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-green-200 bg-green-50 text-green-600">
                                            <span class="material-symbols-outlined text-[22px]" aria-hidden="true">task_alt</span>
                                        </span>
                                        <div>
                                            <h2 class="text-base font-bold leading-snug text-slate-900">Selesaikan {{ $service->type->label() }} {{ $vehicle->plate_number }}</h2>
                                            <p class="mt-0.5 text-xs text-slate-500">Dijadwalkan {{ $service->scheduled_date->format('d/m/Y') }}. Hasil servis tercatat di riwayat.</p>
                                        </div>
                                    </div>
                                    <button type="button" x-on:click="$dispatch('close')" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                                        <span class="material-symbols-outlined" aria-hidden="true">close</span>
                                    </button>
                                </div>
                                @php $mine = (int) old('_service_id') === $service->id; @endphp
                                <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">
                                    <div>
                                        <label for="completed_date_{{ $service->id }}" class="form-label">Tanggal selesai <span class="text-red-600">*</span></label>
                                        <input id="completed_date_{{ $service->id }}" name="completed_date" type="date" required max="{{ today()->toDateString() }}"
                                               value="{{ $mine ? old('completed_date') : today()->min($service->scheduled_date)->toDateString() }}" class="form-input">
                                        @if ($mine)<x-input-error :messages="$errors->completeService->get('completed_date')" class="mt-1.5" />@endif
                                    </div>
                                    <div>
                                        <label for="odometer_at_service_{{ $service->id }}" class="form-label">Odometer saat servis (km) <span class="text-red-600">*</span></label>
                                        <input id="odometer_at_service_{{ $service->id }}" name="odometer_at_service" type="number" min="{{ $odometer ?? 0 }}" required inputmode="numeric"
                                               value="{{ $mine ? old('odometer_at_service') : $odometer }}" class="form-input">
                                        @if ($odometer !== null && ! $mine)<p class="mt-1 text-[11px] text-slate-500">Diisi dari odometer terakhir yang tercatat.</p>@endif
                                        @if ($mine)<x-input-error :messages="$errors->completeService->get('odometer_at_service')" class="mt-1.5" />@endif
                                    </div>
                                    <div>
                                        <label for="cost_{{ $service->id }}" class="form-label">Biaya (Rp)</label>
                                        <input id="cost_{{ $service->id }}" name="cost" type="number" min="0" step="1000" inputmode="numeric"
                                               value="{{ $mine ? old('cost') : '' }}" class="form-input">
                                        @if ($mine)<x-input-error :messages="$errors->completeService->get('cost')" class="mt-1.5" />@endif
                                    </div>
                                    <div>
                                        <label for="workshop_{{ $service->id }}" class="form-label">Bengkel</label>
                                        <input id="workshop_{{ $service->id }}" name="workshop" type="text" maxlength="100"
                                               value="{{ $mine ? old('workshop') : $service->workshop }}" class="form-input">
                                        @if ($mine)<x-input-error :messages="$errors->completeService->get('workshop')" class="mt-1.5" />@endif
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="notes_{{ $service->id }}" class="form-label">Pekerjaan yang dilakukan / catatan</label>
                                        <textarea id="notes_{{ $service->id }}" name="notes" rows="2" maxlength="500" placeholder="mis. Ganti oli mesin & filter, cek rem" class="form-textarea">{{ $mine ? old('notes') : '' }}</textarea>
                                        @if ($mine)<x-input-error :messages="$errors->completeService->get('notes')" class="mt-1.5" />@endif
                                    </div>
                                </div>
                                <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50 px-6 py-4">
                                    <button type="button" x-on:click="$dispatch('close')" class="btn-secondary btn-sm h-[38px]">Batal</button>
                                    <button type="submit" class="btn-success btn-sm h-[38px] font-semibold">
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">task_alt</span>Simpan
                                    </button>
                                </div>
                            </form>
                        </x-modal>
                    @empty
                        <li class="rounded-xl border border-dashed border-slate-300 p-5 text-center text-xs text-slate-500">
                            Belum ada servis terjadwal.
                            <button type="button" x-data x-on:click.prevent="$dispatch('open-modal', 'schedule-service')" class="mt-2 block w-full font-semibold text-brand-700 hover:underline">Jadwalkan sekarang</button>
                        </li>
                    @endforelse
                </ul>
                <p class="mt-4 flex items-start gap-1.5 text-[11px] leading-snug text-slate-500">
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">info</span>
                    Pada hari servis, kendaraan tidak bisa dipesan.
                </p>
            </section>

            <section class="card p-6">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                    <span class="material-symbols-outlined text-[22px] text-brand-700" aria-hidden="true">event_upcoming</span>
                    <h3 class="card-title">Pemesanan Mendatang</h3>
                </div>
                <ul class="mt-3 divide-y divide-slate-100">
                    @forelse ($upcoming as $booking)
                        <li class="flex items-center justify-between gap-2 py-2.5">
                            <div class="min-w-0">
                                <a href="{{ route('bookings.show', $booking) }}" class="font-mono text-xs font-semibold text-brand-700 hover:underline">{{ $booking->code }}</a>
                                <div class="tabular truncate text-[11px] text-slate-500">{{ $booking->start_at->format('d/m H:i') }} &ndash; {{ $booking->end_at->format('d/m H:i') }} &middot; {{ $booking->destination }}</div>
                            </div>
                            <x-status-badge :status="$booking->status" class="shrink-0 text-[11px]" />
                        </li>
                    @empty
                        <li class="py-3 text-xs text-slate-500">Tidak ada pemesanan aktif.</li>
                    @endforelse
                </ul>
            </section>

            <section class="card p-6">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                    <span class="material-symbols-outlined text-[22px] text-brand-700" aria-hidden="true">history</span>
                    <h3 class="card-title">Riwayat Servis</h3>
                </div>
                <ol class="mt-3 space-y-3">
                    @forelse ($history as $service)
                        <li class="rounded-lg border border-slate-200 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="text-xs font-semibold text-slate-900">{{ $service->type->label() }}</div>
                                    <div class="tabular font-mono text-[11px] text-slate-500">{{ ($service->completed_date ?? $service->scheduled_date)->format('d/m/Y') }}@if ($service->workshop) &middot; <span class="font-sans">{{ $service->workshop }}</span>@endif</div>
                                </div>
                                <x-status-badge :status="$service->status" class="shrink-0 text-[11px]" />
                            </div>
                            @if ($service->odometer_at_service || $service->cost)
                                <div class="tabular mt-1.5 flex flex-wrap gap-x-3 font-mono text-[11px] text-slate-700">
                                    @if ($service->odometer_at_service)<span>{{ $num($service->odometer_at_service) }} km</span>@endif
                                    @if ($service->cost)<span>Rp {{ $num((float) $service->cost) }}</span>@endif
                                </div>
                            @endif
                            @if ($service->notes)<p class="mt-1.5 text-[11px] text-slate-600">{{ $service->notes }}</p>@endif
                        </li>
                    @empty
                        <li class="text-xs text-slate-500">Belum ada riwayat servis.</li>
                    @endforelse
                </ol>
            </section>
        </div>
    </div>

    {{-- Modal: jadwalkan servis --}}
    <x-modal name="schedule-service" :show="$errors->schedule->any()" maxWidth="lg" focusable>
        <form method="POST" action="{{ route('services.store', $vehicle) }}">
            @csrf
            <div class="flex items-start justify-between border-b border-slate-100 p-6 pb-4">
                <div class="flex items-start gap-3.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-brand-200 bg-brand-50 text-brand-700">
                        <span class="material-symbols-outlined text-[22px]" aria-hidden="true">build</span>
                    </span>
                    <div>
                        <h2 class="text-base font-bold leading-snug text-slate-900">Jadwalkan servis {{ $vehicle->plate_number }}</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Kendaraan tidak bisa dipesan pada tanggal servis.</p>
                    </div>
                </div>
                <button type="button" x-on:click="$dispatch('close')" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                </button>
            </div>
            <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">
                <div>
                    <label for="type" class="form-label">Jenis servis <span class="text-red-600">*</span></label>
                    <select id="type" name="type" required class="form-input">
                        @foreach ($serviceTypes as $type)
                            <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->schedule->get('type')" class="mt-1.5" />
                </div>
                <div>
                    <label for="scheduled_date" class="form-label">Tanggal servis <span class="text-red-600">*</span></label>
                    <input id="scheduled_date" name="scheduled_date" type="date" required min="{{ today()->toDateString() }}"
                           value="{{ old('scheduled_date') }}" class="form-input">
                    <x-input-error :messages="$errors->schedule->get('scheduled_date')" class="mt-1.5" />
                </div>
                <div>
                    <label for="odometer_due" class="form-label">Target odometer (km)</label>
                    <input id="odometer_due" name="odometer_due" type="number" min="0" inputmode="numeric"
                           value="{{ old('odometer_due') }}" placeholder="{{ $odometer !== null ? 'mis. '.($odometer - $odometer % 5000 + 5000) : '' }}" class="form-input">
                    <p class="mt-1 text-[11px] text-slate-500">Opsional, untuk servis berkala per kilometer.</p>
                    <x-input-error :messages="$errors->schedule->get('odometer_due')" class="mt-1.5" />
                </div>
                <div>
                    <label for="workshop" class="form-label">Bengkel</label>
                    <input id="workshop" name="workshop" type="text" maxlength="100" value="{{ old('workshop') }}" placeholder="mis. Bengkel Pool Morowali" class="form-input">
                    <x-input-error :messages="$errors->schedule->get('workshop')" class="mt-1.5" />
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="form-label">Rencana pekerjaan</label>
                    <input id="description" name="description" type="text" maxlength="255" value="{{ old('description') }}" placeholder="mis. Ganti oli, filter udara, cek kaki-kaki" class="form-input">
                    <x-input-error :messages="$errors->schedule->get('description')" class="mt-1.5" />
                </div>
            </div>
            <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50 px-6 py-4">
                <button type="button" x-on:click="$dispatch('close')" class="btn-secondary btn-sm h-[38px]">Batal</button>
                <button type="submit" class="btn-primary btn-sm h-[38px] font-semibold">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">event_available</span>Simpan Jadwal
                </button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
