<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Laporan Pemesanan</h2>
                <p class="text-sm text-gray-500">
                    Periode {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d/m/Y') }}
                    s/d {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d/m/Y') }}
                </p>
            </div>
            <a href="{{ route('reports.export', array_filter($filters, fn ($v) => $v !== null && $v !== '')) }}"
               class="inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                Export Excel
            </a>
        </div>
    </x-slot>

    @php $selectClass = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500'; @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
            {{-- Filter --}}
            <form method="GET" class="grid grid-cols-1 gap-3 rounded-lg bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
                <div>
                    <x-input-label for="from" value="Dari tanggal" />
                    <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="$filters['from']" required />
                </div>
                <div>
                    <x-input-label for="to" value="Sampai tanggal" />
                    <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="$filters['to']" required />
                </div>
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="{{ $selectClass }}">
                        <option value="">Semua</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="type" value="Jenis angkutan" />
                    <select id="type" name="type" class="{{ $selectClass }}">
                        <option value="">Semua</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="location_id" value="Lokasi kendaraan" />
                    <select id="location_id" name="location_id" class="{{ $selectClass }}">
                        <option value="">Semua</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected((string) ($filters['location_id'] ?? '') === (string) $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <x-primary-button>Tampilkan</x-primary-button>
                    <a href="{{ route('reports.index') }}" class="px-2 py-2 text-xs font-semibold uppercase tracking-widest text-gray-600 hover:text-gray-900">Reset</a>
                </div>
                <x-input-error :messages="$errors->all()" class="sm:col-span-2 lg:col-span-6" />
            </form>

            {{-- Ringkasan --}}
            <dl class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-7">
                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <dt class="text-xs text-gray-500">Total pemesanan</dt>
                    <dd class="text-2xl font-bold text-gray-900">{{ $summary['total'] }}</dd>
                </div>
                @foreach ($statuses as $status)
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <dt class="text-xs text-gray-500">{{ $status->label() }}</dt>
                        <dd class="text-2xl font-bold text-gray-900">{{ $summary['by_status'][$status->value] }}</dd>
                    </div>
                @endforeach
                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <dt class="text-xs text-gray-500">Pemakaian</dt>
                    <dd class="text-sm font-semibold text-gray-900">{{ number_format($summary['hours'], 1, ',', '.') }} jam</dd>
                    <dd class="text-xs text-gray-600">{{ number_format($summary['km'], 0, ',', '.') }} km &middot; {{ number_format($summary['liters'], 1, ',', '.') }} L</dd>
                </div>
            </dl>

            {{-- Preview --}}
            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Pemohon / Tujuan</th>
                            <th class="px-4 py-3">Kendaraan</th>
                            <th class="px-4 py-3">Driver</th>
                            <th class="px-4 py-3">Jadwal</th>
                            <th class="px-4 py-3">Persetujuan</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($bookings as $booking)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3"><a href="{{ route('bookings.show', $booking) }}" class="font-mono text-xs text-indigo-600 hover:text-indigo-900">{{ $booking->code }}</a></td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $booking->requester_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $booking->destination }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    {{ $booking->vehicle->plate_number }}
                                    <div class="text-xs text-gray-500">{{ $booking->vehicle->type->label() }} &middot; {{ $booking->vehicle->ownership->label() }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $booking->driver->name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs">
                                    {{ $booking->start_at->format('d/m/Y H:i') }}<br>
                                    <span class="text-gray-500">s/d {{ $booking->end_at->format('d/m/Y H:i') }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    @foreach ($booking->approvals as $approval)
                                        <div>L{{ $approval->level }} {{ $approval->approver->name }}: <span class="font-medium">{{ $approval->status->label() }}</span></div>
                                    @endforeach
                                </td>
                                <td class="whitespace-nowrap px-4 py-3"><x-status-badge :status="$booking->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Tidak ada pemesanan pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $bookings->links() }}</div>
        </div>
    </div>
</x-app-layout>
