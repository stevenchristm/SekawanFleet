<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pemesanan Kendaraan</h2>
            <a href="{{ route('bookings.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                + Buat Pemesanan
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash />

            {{-- Filter --}}
            <form method="GET" class="mb-4 grid grid-cols-1 gap-3 rounded-lg bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <x-input-label for="q" value="Cari (kode, pemohon, tujuan)" />
                    <x-text-input id="q" name="q" type="text" class="mt-1 block w-full" :value="$filters['q'] ?? ''" />
                </div>
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="from" value="Dari tanggal" />
                    <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="$filters['from'] ?? ''" />
                </div>
                <div>
                    <x-input-label for="to" value="Sampai tanggal" />
                    <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="$filters['to'] ?? ''" />
                </div>
                <div class="flex gap-2 sm:col-span-2 lg:col-span-5">
                    <x-primary-button>Filter</x-primary-button>
                    <a href="{{ route('bookings.index') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-600 hover:text-gray-900">Reset</a>
                </div>
            </form>
            <x-input-error :messages="$errors->all()" class="mb-4" />

            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Pemohon</th>
                            <th class="px-4 py-3">Kendaraan</th>
                            <th class="px-4 py-3">Driver</th>
                            <th class="px-4 py-3">Jadwal</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($bookings as $booking)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ $booking->code }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $booking->requester_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $booking->destination }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $booking->vehicle->plate_number }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $booking->driver->name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs">
                                    {{ $booking->start_at->format('d/m/Y H:i') }}<br>
                                    <span class="text-gray-500">s/d {{ $booking->end_at->format('d/m/Y H:i') }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <x-status-badge :status="$booking->status" />
                                    @if ($booking->currentApproval)
                                        <div class="mt-1 text-xs text-gray-500">L{{ $booking->currentApproval->level }}: {{ $booking->currentApproval->approver->name }}</div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <a href="{{ route('bookings.show', $booking) }}" class="text-indigo-600 hover:text-indigo-900">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada pemesanan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $bookings->links() }}</div>
        </div>
    </div>
</x-app-layout>
