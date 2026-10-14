<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Persetujuan Saya</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash />
            <x-input-error :messages="$errors->get('approval')" class="mb-4" />

            {{-- Tab --}}
            <nav class="mb-4 flex gap-2 border-b border-gray-200" aria-label="Tab persetujuan">
                @foreach (['pending' => 'Menunggu Saya', 'history' => 'Riwayat'] as $key => $label)
                    <a href="{{ route('approvals.index', ['tab' => $key]) }}"
                       @class([
                           '-mb-px border-b-2 px-4 py-2 text-sm font-medium',
                           'border-indigo-500 text-indigo-600' => $tab === $key,
                           'border-transparent text-gray-500 hover:text-gray-700' => $tab !== $key,
                       ])
                       @if ($tab === $key) aria-current="page" @endif>
                        {{ $label }}
                        @if ($key === 'pending' && $pendingCount > 0)
                            <span class="ms-1 rounded-full bg-red-500 px-2 py-0.5 text-xs text-white">{{ $pendingCount }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="space-y-4">
                @forelse ($approvals as $approval)
                    @php $booking = $approval->booking; @endphp
                    <article class="rounded-lg bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <a href="{{ route('bookings.show', $booking) }}" class="font-mono text-sm text-indigo-600 hover:text-indigo-900">{{ $booking->code }}</a>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $booking->requester_name }} &rarr; {{ $booking->destination }}</h3>
                                <p class="text-sm text-gray-600">{{ $booking->purpose }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-semibold uppercase text-gray-500">Level {{ $approval->level }} dari {{ $booking->approvals->count() }}</span>
                                <div class="mt-1"><x-status-badge :status="$tab === 'pending' ? $booking->status : $approval->status" /></div>
                            </div>
                        </div>

                        <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                            <div><dt class="text-gray-500">Jadwal</dt><dd class="text-gray-900">{{ $booking->start_at->format('d/m/Y H:i') }} - {{ $booking->end_at->format('d/m/Y H:i') }}</dd></div>
                            <div><dt class="text-gray-500">Kendaraan</dt><dd class="text-gray-900">{{ $booking->vehicle->plate_number }} ({{ $booking->vehicle->brand_model }})</dd></div>
                            <div><dt class="text-gray-500">Driver</dt><dd class="text-gray-900">{{ $booking->driver->name }}</dd></div>
                        </dl>

                        {{-- Ringkasan level sebelumnya --}}
                        <ul class="mt-3 flex flex-wrap gap-2 text-xs">
                            @foreach ($booking->approvals as $step)
                                <li class="rounded bg-gray-50 px-2 py-1 text-gray-700">
                                    L{{ $step->level }} {{ $step->approver->name }}: <span class="font-medium">{{ $step->status->label() }}</span>
                                </li>
                            @endforeach
                        </ul>

                        @if ($tab === 'pending')
                            <div class="mt-4 border-t pt-4">
                                @include('approvals._actions', ['approval' => $approval])
                            </div>
                        @elseif ($approval->note)
                            <p class="mt-3 rounded bg-gray-50 p-2 text-sm text-gray-700">Catatan Anda: "{{ $approval->note }}"</p>
                        @endif
                    </article>
                @empty
                    <div class="rounded-lg bg-white p-8 text-center text-gray-500 shadow-sm">
                        {{ $tab === 'pending' ? 'Tidak ada pemesanan yang menunggu persetujuan Anda.' : 'Belum ada riwayat persetujuan.' }}
                    </div>
                @endforelse
            </div>

            <div class="mt-4">{{ $approvals->links() }}</div>
        </div>
    </div>
</x-app-layout>
