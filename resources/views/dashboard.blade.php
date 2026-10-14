<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
                <p class="text-sm text-gray-500">Pemakaian kendaraan {{ $months }} bulan terakhir</p>
            </div>
            <form method="GET" class="flex items-center gap-2">
                <label for="months" class="text-sm text-gray-600">Periode</label>
                <select id="months" name="months" onchange="this.form.submit()"
                        class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($periods as $p)
                        <option value="{{ $p }}" @selected($p === $months)>{{ $p }} bulan</option>
                    @endforeach
                </select>
                <noscript><x-primary-button>Terapkan</x-primary-button></noscript>
            </form>
        </div>
    </x-slot>

    @php
        $cards = [
            ['label' => 'Pemesanan bulan ini', 'value' => $kpis['bookings_this_month'], 'tone' => 'text-indigo-600'],
            ['label' => 'Menunggu persetujuan', 'value' => $kpis['pending'], 'tone' => 'text-yellow-600'],
            ['label' => 'Kendaraan dipakai hari ini', 'value' => $kpis['in_use_today'].' / '.$kpis['vehicles_total'], 'tone' => 'text-green-600'],
            ['label' => 'Kendaraan tersedia', 'value' => $kpis['vehicles_available'], 'tone' => 'text-blue-600'],
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
            @if ($approverKpis)
                <section aria-labelledby="my-approvals" class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 id="my-approvals" class="font-semibold text-yellow-900">Persetujuan Saya</h3>
                        <a href="{{ route('approvals.index') }}" class="text-sm font-medium text-yellow-900 underline hover:no-underline">Buka inbox &rarr;</a>
                    </div>
                    <dl class="mt-3 grid grid-cols-3 gap-4 text-center">
                        <div><dt class="text-xs text-yellow-800">Menunggu saya</dt><dd class="text-2xl font-bold text-yellow-900">{{ $approverKpis['waiting_me'] }}</dd></div>
                        <div><dt class="text-xs text-yellow-800">Disetujui bulan ini</dt><dd class="text-2xl font-bold text-yellow-900">{{ $approverKpis['approved_this_month'] }}</dd></div>
                        <div><dt class="text-xs text-yellow-800">Ditolak bulan ini</dt><dd class="text-2xl font-bold text-yellow-900">{{ $approverKpis['rejected_this_month'] }}</dd></div>
                    </dl>
                </section>
            @endif

            {{-- KPI --}}
            <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ($cards as $card)
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <dt class="text-sm text-gray-500">{{ $card['label'] }}</dt>
                        <dd class="mt-1 text-3xl font-bold {{ $card['tone'] }}">{{ $card['value'] }}</dd>
                    </div>
                @endforeach
            </dl>

            {{-- Grafik --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <section class="rounded-lg bg-white p-5 shadow-sm lg:col-span-2">
                    <h3 class="mb-3 font-semibold text-gray-800">Pemesanan per Bulan</h3>
                    <div class="h-72"><canvas id="chart-monthly" role="img" aria-label="Grafik batang jumlah pemesanan per bulan menurut status"></canvas></div>
                </section>
                <section class="rounded-lg bg-white p-5 shadow-sm">
                    <h3 class="mb-3 font-semibold text-gray-800">Komposisi Status</h3>
                    <div class="h-72"><canvas id="chart-status" role="img" aria-label="Grafik donat komposisi status pemesanan"></canvas></div>
                </section>
                <section class="rounded-lg bg-white p-5 shadow-sm lg:col-span-2">
                    <h3 class="mb-3 font-semibold text-gray-800">Kendaraan Paling Sering Dipakai</h3>
                    <div class="h-80"><canvas id="chart-top-vehicles" role="img" aria-label="Grafik 10 kendaraan dengan perjalanan dan jam pemakaian terbanyak"></canvas></div>
                </section>
                <section class="rounded-lg bg-white p-5 shadow-sm">
                    <h3 class="mb-3 font-semibold text-gray-800">Pemakaian per Jenis Angkutan</h3>
                    <div class="h-80"><canvas id="chart-type" role="img" aria-label="Grafik pemakaian angkutan orang dan barang, milik sendiri dan sewa"></canvas></div>
                </section>
                <section class="rounded-lg bg-white p-5 shadow-sm lg:col-span-3">
                    <h3 class="mb-3 font-semibold text-gray-800">Konsumsi BBM &amp; Jarak Tempuh</h3>
                    <div class="h-72"><canvas id="chart-fuel" role="img" aria-label="Grafik garis konsumsi BBM dan jarak tempuh per bulan"></canvas></div>
                </section>
            </div>

            @if ($recent->isNotEmpty())
                <section class="rounded-lg bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b px-5 py-4">
                        <h3 class="font-semibold text-gray-800">Pemesanan Terbaru</h3>
                        <a href="{{ route('bookings.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">Lihat semua</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($recent as $booking)
                                    <tr>
                                        <td class="whitespace-nowrap px-5 py-3"><a href="{{ route('bookings.show', $booking) }}" class="font-mono text-xs text-indigo-600 hover:text-indigo-900">{{ $booking->code }}</a></td>
                                        <td class="px-5 py-3">{{ $booking->requester_name }}</td>
                                        <td class="whitespace-nowrap px-5 py-3">{{ $booking->vehicle->plate_number }}</td>
                                        <td class="whitespace-nowrap px-5 py-3 text-xs">{{ $booking->start_at->format('d/m/Y H:i') }}</td>
                                        <td class="whitespace-nowrap px-5 py-3"><x-status-badge :status="$booking->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>
    </div>

    <script type="application/json" id="dashboard-data">@json($charts)</script>
</x-app-layout>
