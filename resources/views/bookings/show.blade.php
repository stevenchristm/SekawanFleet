<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pemesanan {{ $booking->code }}</h2>
                <p class="text-sm text-gray-500">Dibuat oleh {{ $booking->creator->name }} pada {{ $booking->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <x-status-badge :status="$booking->status" class="text-sm" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash />
            <x-input-error :messages="$errors->get('booking')" class="mb-4" />

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Detail --}}
                <div class="space-y-6 lg:col-span-2">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500">Detail Pemesanan</h3>
                        <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                            <div><dt class="text-gray-500">Pemohon</dt><dd class="font-medium text-gray-900">{{ $booking->requester_name }}</dd></div>
                            <div><dt class="text-gray-500">Tujuan</dt><dd class="font-medium text-gray-900">{{ $booking->destination }}</dd></div>
                            <div><dt class="text-gray-500">Mulai</dt><dd class="font-medium text-gray-900">{{ $booking->start_at->format('d/m/Y H:i') }}</dd></div>
                            <div><dt class="text-gray-500">Selesai</dt><dd class="font-medium text-gray-900">{{ $booking->end_at->format('d/m/Y H:i') }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-gray-500">Keperluan</dt><dd class="text-gray-900">{{ $booking->purpose }}</dd></div>
                        </dl>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500">Kendaraan & Driver</h3>
                        <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                            <div><dt class="text-gray-500">Kendaraan</dt><dd class="font-medium text-gray-900">{{ $booking->vehicle->plate_number }} - {{ $booking->vehicle->brand_model }}</dd></div>
                            <div><dt class="text-gray-500">Jenis</dt><dd class="text-gray-900">{{ $booking->vehicle->type->label() }}</dd></div>
                            <div>
                                <dt class="text-gray-500">Kepemilikan</dt>
                                <dd class="text-gray-900">
                                    {{ $booking->vehicle->ownership->label() }}
                                    @if ($booking->vehicle->rentalCompany) ({{ $booking->vehicle->rentalCompany->name }}) @endif
                                </dd>
                            </div>
                            <div><dt class="text-gray-500">Lokasi</dt><dd class="text-gray-900">{{ $booking->vehicle->location->name }}</dd></div>
                            <div><dt class="text-gray-500">Driver</dt><dd class="font-medium text-gray-900">{{ $booking->driver->name }}</dd></div>
                            <div><dt class="text-gray-500">Telepon driver</dt><dd class="text-gray-900">{{ $booking->driver->phone ?? '-' }}</dd></div>
                        </dl>
                    </div>
                </div>

                {{-- Timeline persetujuan --}}
                <div class="space-y-6">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500">Persetujuan</h3>
                        <ol class="relative space-y-6 border-s border-gray-200 ps-6">
                            @foreach ($booking->approvals as $approval)
                                <li>
                                    <span @class([
                                        'absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white',
                                        'bg-green-500' => $approval->status === \App\Enums\ApprovalStatus::Approved,
                                        'bg-red-500' => $approval->status === \App\Enums\ApprovalStatus::Rejected,
                                        'bg-yellow-400' => $approval->status === \App\Enums\ApprovalStatus::Pending,
                                        'bg-gray-300' => in_array($approval->status, [\App\Enums\ApprovalStatus::Waiting, \App\Enums\ApprovalStatus::Skipped], true),
                                    ])></span>
                                    <p class="text-xs font-semibold uppercase text-gray-500">Level {{ $approval->level }}</p>
                                    <p class="font-medium text-gray-900">{{ $approval->approver->name }}</p>
                                    @if ($approval->approver->position)
                                        <p class="text-xs text-gray-500">{{ $approval->approver->position }}</p>
                                    @endif
                                    <x-status-badge :status="$approval->status" class="mt-1" />
                                    @if ($approval->acted_at)
                                        <p class="mt-1 text-xs text-gray-500">{{ $approval->acted_at->format('d/m/Y H:i') }}</p>
                                    @endif
                                    @if ($approval->note)
                                        <p class="mt-1 rounded bg-gray-50 p-2 text-xs text-gray-700">"{{ $approval->note }}"</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </div>

                    @can('cancel', $booking)
                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}"
                              onsubmit="return confirm('Batalkan pemesanan {{ $booking->code }}?');">
                            @csrf
                            @method('PATCH')
                            <x-danger-button class="w-full justify-center">Batalkan Pemesanan</x-danger-button>
                        </form>
                    @endcan
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ auth()->user()->isAdmin() ? route('bookings.index') : route('approvals.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Kembali</a>
            </div>
        </div>
    </div>
</x-app-layout>
