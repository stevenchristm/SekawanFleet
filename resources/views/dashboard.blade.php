<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>
    <x-slot name="subtitle">Pemakaian kendaraan {{ $months }} bulan terakhir di seluruh lokasi tambang &amp; kantor</x-slot>

    @php
        $url = fn (array $override) => route('dashboard', array_filter(array_merge(['months' => $months, 'location' => $locationId], $override), fn ($v) => $v !== null));
        $status = $charts['status'];
        $top = $charts['topVehicles'];
        $type = $charts['byType'];
        $maxTrips = max($top['trips'] ?: [1]);
        $typeTotals = [$type['owned'][0] + $type['rented'][0], $type['owned'][1] + $type['rented'][1]];
        $tripsAll = array_sum($typeTotals);
        $typeGrand = max($tripsAll, 1);
        $ownedPct = $tripsAll ? (int) round(array_sum($type['owned']) / $tripsAll * 100) : 0;
        $rentedPct = $tripsAll ? 100 - $ownedPct : 0;
        $statusColors = ['pending' => 'bg-amber-500', 'approved' => 'bg-emerald-500', 'rejected' => 'bg-rose-500', 'completed' => 'bg-blue-500', 'cancelled' => 'bg-slate-400'];
    @endphp

    <x-slot name="actions">
        <form method="GET" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-700">
            @if ($locationId)<input type="hidden" name="location" value="{{ $locationId }}">@endif
            <span class="material-symbols-outlined text-[18px] text-slate-400" aria-hidden="true">calendar_month</span>
            <label for="months" class="text-[11px] font-semibold uppercase text-slate-400">Periode:</label>
            <select id="months" name="months" onchange="this.form.submit()" class="cursor-pointer border-none bg-transparent p-0 pr-6 text-sm font-medium text-slate-800 focus:ring-0">
                @foreach ($periods as $p)
                    <option value="{{ $p }}" @selected($p === $months)>{{ $p }} bulan</option>
                @endforeach
            </select>
            <noscript><button class="btn-sm btn-secondary">Terapkan</button></noscript>
        </form>
    </x-slot>

    {{-- Filter lokasi --}}
    <nav aria-label="Filter lokasi" class="flex overflow-x-auto rounded-xl border border-slate-200 bg-slate-100/80 p-1.5">
        <div class="flex items-center gap-1 text-xs font-medium text-slate-600">
            <a href="{{ $url(['location' => null]) }}" @class([
                'whitespace-nowrap rounded-lg px-3 py-1.5 transition-colors',
                'border border-slate-200/60 bg-white font-semibold text-brand-700 shadow-sm' => ! $locationId,
                'hover:bg-white/60 hover:text-slate-900' => $locationId,
            ]) @if (! $locationId) aria-current="true" @endif>Semua Lokasi ({{ $locations->count() }})</a>
            @foreach ($locations as $location)
                <a href="{{ $url(['location' => $location->id]) }}" @class([
                    'whitespace-nowrap rounded-lg px-3 py-1.5 transition-colors',
                    'border border-slate-200/60 bg-white font-semibold text-brand-700 shadow-sm' => $locationId === $location->id,
                    'hover:bg-white/60 hover:text-slate-900' => $locationId !== $location->id,
                ]) @if ($locationId === $location->id) aria-current="true" @endif>{{ $location->name }}</a>
            @endforeach
        </div>
    </nav>

    {{-- Panel approver --}}
    @if ($approverKpis)
        <section aria-labelledby="my-approvals" class="rounded-xl border border-amber-200 bg-amber-50 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-600" aria-hidden="true">notification_important</span>
                    <h3 id="my-approvals" class="font-semibold text-amber-950">Persetujuan Saya</h3>
                </div>
                <a href="{{ route('approvals.index') }}" class="btn-sm btn-secondary">Buka inbox <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_forward</span></a>
            </div>
            <dl class="mt-4 grid grid-cols-3 gap-4 text-center">
                <div><dt class="text-xs text-amber-800">Menunggu saya</dt><dd class="tabular font-mono text-2xl font-bold text-amber-950">{{ $approverKpis['waiting_me'] }}</dd></div>
                <div><dt class="text-xs text-amber-800">Disetujui bulan ini</dt><dd class="tabular font-mono text-2xl font-bold text-amber-950">{{ $approverKpis['approved_this_month'] }}</dd></div>
                <div><dt class="text-xs text-amber-800">Ditolak bulan ini</dt><dd class="tabular font-mono text-2xl font-bold text-amber-950">{{ $approverKpis['rejected_this_month'] }}</dd></div>
            </dl>
        </section>
    @endif

    {{-- KPI --}}
    <section aria-label="Ringkasan" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $trend = $kpis['bookings_trend'];
            $cards = [
                ['Pemesanan bulan ini', $kpis['bookings_this_month'], null, 'book_online', 'bg-brand-50 border-brand-100 text-brand-700',
                    $trend === null ? ['remove', 'Belum ada data bulan lalu', 'text-slate-500']
                        : [$trend >= 0 ? 'trending_up' : 'trending_down', ($trend >= 0 ? '+' : '').$trend.'% vs bulan lalu', $trend >= 0 ? 'text-emerald-700' : 'text-rose-700']],
                ['Menunggu persetujuan', $kpis['pending'], null, 'pending_actions', 'bg-amber-50 border-amber-100 text-amber-600',
                    $kpis['pending'] > 0 ? ['warning', 'Perlu tindakan approver', 'text-amber-700'] : ['check', 'Tidak ada antrean', 'text-emerald-700']],
                ['Kendaraan dipakai hari ini', $kpis['in_use_today'], $kpis['vehicles_total'], 'commute', 'bg-blue-50 border-blue-100 text-blue-600',
                    ['pie_chart', $kpis['in_use_percent'].'% kapasitas terpakai', 'text-blue-700']],
                ['Kendaraan tersedia', $kpis['vehicles_available'], null, 'check_circle', 'bg-emerald-50 border-emerald-100 text-emerald-600',
                    ['fmd_good', 'Siap ditugaskan', 'text-emerald-700']],
            ];
        @endphp
        @foreach ($cards as [$label, $value, $of, $icon, $tone, [$hintIcon, $hint, $hintTone]])
            <div class="card p-5 transition-colors hover:border-slate-300">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                        <p class="tabular mt-2 font-mono text-3xl font-bold tracking-tight text-slate-900">
                            {{ $value }}@if ($of !== null) <span class="text-lg font-normal text-slate-400">/ {{ $of }}</span>@endif
                        </p>
                    </div>
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl border {{ $tone }}">
                        <span class="material-symbols-outlined text-2xl" aria-hidden="true">{{ $icon }}</span>
                    </span>
                </div>
                <p class="mt-3 flex items-center gap-1.5 text-xs font-medium {{ $hintTone }}">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">{{ $hintIcon }}</span>{{ $hint }}
                </p>
            </div>
        @endforeach
    </section>

    {{-- Baris 1: per bulan + status --}}
    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-2">
            <div class="flex flex-col justify-between gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-center">
                <div>
                    <h3 class="card-title">Pemesanan per Bulan</h3>
                    <p class="card-subtitle">Distribusi pemesanan periode {{ $charts['monthly']['labels'][0] ?? '' }} &ndash; {{ last($charts['monthly']['labels']) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-xs font-medium text-slate-600">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span>Disetujui/Selesai</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-amber-500"></span>Menunggu</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-rose-500"></span>Ditolak/Batal</span>
                </div>
            </div>
            <div class="h-64 pt-4"><canvas id="chart-monthly" role="img" aria-label="Grafik batang jumlah pemesanan per bulan menurut status"></canvas></div>
        </div>

        <div class="card flex flex-col justify-between p-6">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="card-title">Komposisi Status</h3>
                    <span class="tabular font-mono text-xs text-slate-400">Total {{ $status['total'] }}</span>
                </div>
                <p class="card-subtitle mt-1">Proporsi keputusan alur persetujuan</p>
                <div class="relative mx-auto my-4 h-44 w-44">
                    <canvas id="chart-status" role="img" aria-label="Grafik donat komposisi status pemesanan"></canvas>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="tabular font-mono text-2xl font-bold text-slate-900">{{ $status['approved_percent'] }}%</span>
                        <span class="text-[11px] font-medium text-slate-500">Disetujui</span>
                    </div>
                </div>
            </div>
            <ul class="space-y-2 border-t border-slate-100 pt-3 text-xs">
                @foreach ($status['keys'] as $i => $key)
                    <li class="flex items-center justify-between">
                        <span class="flex items-center gap-2 font-medium text-slate-700"><span class="h-2.5 w-2.5 rounded-full {{ $statusColors[$key] }}"></span>{{ $status['labels'][$i] }}</span>
                        <span class="tabular font-mono font-semibold text-slate-900">{{ $status['data'][$i] }} ({{ $status['total'] ? round($status['data'][$i] / $status['total'] * 100) : 0 }}%)</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Baris 2: top kendaraan + jenis angkutan --}}
    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <h3 class="card-title">Kendaraan Paling Sering Dipakai</h3>
                    <p class="card-subtitle">Peringkat {{ count($top['labels']) }} unit dengan perjalanan &amp; jam pemakaian tertinggi</p>
                </div>
                <span class="rounded-md bg-slate-100 px-2.5 py-1 font-mono text-xs text-slate-600">Top 10</span>
            </div>
            <ol class="mt-4 space-y-3">
                @forelse ($top['labels'] as $i => $plate)
                    <li class="space-y-1">
                        <div class="flex flex-wrap items-center justify-between gap-x-3 text-xs">
                            <span class="font-medium text-slate-800">
                                <span class="font-mono font-semibold text-brand-700">{{ $i + 1 }}.</span> {{ $plate }}
                                <span class="text-slate-500">({{ $top['models'][$i] }} &middot; {{ $top['locations'][$i] }})</span>
                            </span>
                            <span class="tabular font-mono font-medium text-slate-600">{{ $top['trips'][$i] }} perjalanan <span class="text-slate-400">&middot; {{ rtrim(rtrim(number_format($top['hours'][$i], 1, ',', '.'), '0'), ',') }} jam</span></span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100" role="presentation">
                            <div @class(['h-2 rounded-full', 'bg-brand-700' => $i < 3, 'bg-brand-600' => $i >= 3 && $i < 6, 'bg-slate-400' => $i >= 6])
                                 style="width: {{ round($top['trips'][$i] / $maxTrips * 100) }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="py-8 text-center text-slate-500">Belum ada kendaraan terpakai pada periode ini.</li>
                @endforelse
            </ol>
        </div>

        <div class="card flex flex-col justify-between p-6">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="card-title">Pemakaian per Jenis Angkutan</h3>
                    <span class="font-mono text-xs text-slate-400">Kepemilikan</span>
                </div>
                <p class="card-subtitle mt-1">Perbandingan armada milik perusahaan vs sewa</p>
                <div class="mt-5 space-y-4">
                    @foreach ($type['labels'] as $i => $label)
                        @php $sum = max($typeTotals[$i], 1); @endphp
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3.5">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="flex items-center gap-2 text-xs font-semibold text-slate-800">
                                    <span @class(['material-symbols-outlined text-lg', 'text-brand-700' => $i === 0, 'text-amber-600' => $i === 1]) aria-hidden="true">{{ $i === 0 ? 'groups' : 'inventory_2' }}</span>{{ $label }}
                                </span>
                                <span class="tabular font-mono text-xs font-bold text-slate-900">{{ $typeTotals[$i] }} trip ({{ round($typeTotals[$i] / $typeGrand * 100) }}%)</span>
                            </div>
                            @foreach ([['Milik Perusahaan', $type['owned'][$i], 'bg-brand-700'], ['Sewa', $type['rented'][$i], 'bg-accent-500']] as [$own, $count, $bar])
                                <div class="mt-2">
                                    <div class="mb-1 flex justify-between text-[11px] text-slate-600"><span>{{ $own }} ({{ $count }} trip)</span><span class="font-mono font-medium">{{ round($count / $sum * 100) }}%</span></div>
                                    <div class="h-2.5 w-full rounded-full bg-slate-200"><div class="h-2.5 rounded-full {{ $bar }}" style="width: {{ round($count / $sum * 100) }}%"></div></div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="mt-4 flex items-center justify-center gap-5 border-t border-slate-100 pt-4 text-xs font-medium text-slate-700">
                <span class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-brand-700"></span>Milik ({{ $ownedPct }}%)</span>
                <span class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-accent-500"></span>Sewa ({{ $rentedPct }}%)</span>
            </div>
        </div>
    </section>

    {{-- Baris 3: BBM --}}
    @php
        $fuel = $charts['fuel'];
        $avgL = count($fuel['liters']) ? array_sum($fuel['liters']) / count($fuel['liters']) : 0;
        $avgKm = count($fuel['km']) ? array_sum($fuel['km']) / count($fuel['km']) : 0;
        $ratio = array_sum($fuel['liters']) > 0 ? array_sum($fuel['km']) / array_sum($fuel['liters']) : null;
    @endphp
    <section class="card p-6">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-100 pb-4 md:flex-row md:items-center">
            <div>
                <h3 class="card-title">Konsumsi BBM &amp; Jarak Tempuh</h3>
                <p class="card-subtitle">Dari data pengembalian kendaraan (pemesanan selesai)</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-xs font-medium">
                <span class="flex items-center gap-2 rounded-lg border border-brand-100 bg-brand-50/70 px-3 py-1.5 text-brand-900"><span class="h-2 w-2 rounded-full bg-brand-700"></span>BBM: <strong class="tabular">{{ number_format($avgL, 0, ',', '.') }} L/bln</strong> (rata-rata)</span>
                <span class="flex items-center gap-2 rounded-lg border border-amber-100 bg-amber-50/70 px-3 py-1.5 text-amber-900"><span class="h-2 w-2 rounded-full bg-accent-500"></span>Jarak: <strong class="tabular">{{ number_format($avgKm, 0, ',', '.') }} km/bln</strong> (rata-rata)</span>
            </div>
        </div>
        <div class="h-64 pt-4"><canvas id="chart-fuel" role="img" aria-label="Grafik garis konsumsi BBM dan jarak tempuh per bulan"></canvas></div>
        @if ($ratio)
            <p class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500"><span class="font-semibold text-slate-700">Rasio konsumsi armada:</span> {{ number_format($ratio, 2, ',', '.') }} km per liter selama periode ini.</p>
        @endif
    </section>

    {{-- Pemesanan terbaru (admin) --}}
    @if ($recent->isNotEmpty())
        <section class="card overflow-hidden">
            <div class="card-header">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-brand-200 bg-brand-50 text-brand-700"><span class="material-symbols-outlined" aria-hidden="true">receipt_long</span></span>
                    <div>
                        <h3 class="card-title">Pemesanan Terbaru</h3>
                        <p class="card-subtitle">Pemesanan kendaraan dan status persetujuan berjenjang</p>
                    </div>
                </div>
                <a href="{{ route('bookings.index') }}" class="btn-sm btn-secondary text-brand-700">Lihat semua <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_forward</span></a>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Kode</th><th>Pemohon</th><th>Kendaraan &amp; Lokasi</th><th>Jadwal &amp; Tujuan</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody class="text-xs">
                        @foreach ($recent as $booking)
                            <tr>
                                <td class="whitespace-nowrap font-mono font-semibold text-slate-800">{{ $booking->code }}</td>
                                <td><div class="font-semibold text-slate-900">{{ $booking->requester_name }}</div></td>
                                <td>
                                    <div class="font-medium text-slate-800">{{ $booking->vehicle->plate_number }} &ndash; {{ $booking->vehicle->brand_model }}</div>
                                    <div class="flex items-center gap-1 text-[11px] text-slate-500"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>{{ $booking->vehicle->location->name }}</div>
                                </td>
                                <td>
                                    <div class="tabular font-medium text-slate-800">{{ $booking->start_at->locale('id')->translatedFormat('d M Y, H:i') }} &ndash; {{ $booking->end_at->format('H:i') }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $booking->destination }}</div>
                                </td>
                                <td><x-status-badge :status="$booking->status" /></td>
                                <td class="text-right">
                                    <a href="{{ route('bookings.show', $booking) }}" class="btn-sm btn-secondary hover:text-brand-700"><span class="material-symbols-outlined text-base" aria-hidden="true">visibility</span>Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <script type="application/json" id="dashboard-data">@json($charts)</script>
</x-app-layout>
