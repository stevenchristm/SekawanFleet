<x-app-layout>
    <x-slot name="title">Detail Pemesanan</x-slot>
    <x-slot name="subtitle">Pemesanan Kendaraan / {{ $booking->code }}</x-slot>
    <x-slot name="actions">
        <a href="{{ auth()->user()->isAdmin() ? route('bookings.index') : route('approvals.index') }}" class="btn-ghost">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">arrow_back</span>Kembali
        </a>
    </x-slot>

    @php
        $hours = round($booking->start_at->diffInMinutes($booking->end_at) / 60, 1);
        $myTurn = $booking->approvals->first(fn ($a) => $a->approver_id === auth()->id() && $a->status === \App\Enums\ApprovalStatus::Pending);
        $marker = fn (\App\Enums\ApprovalStatus $s) => match ($s) {
            \App\Enums\ApprovalStatus::Approved => ['check', 'border-green-700 bg-green-100 text-green-700'],
            \App\Enums\ApprovalStatus::Rejected => ['close', 'border-red-600 bg-red-100 text-red-600'],
            \App\Enums\ApprovalStatus::Pending => ['schedule', 'border-amber-500 bg-amber-100 text-amber-700'],
            \App\Enums\ApprovalStatus::Skipped => ['remove', 'border-slate-400 bg-slate-100 text-slate-500'],
            \App\Enums\ApprovalStatus::Waiting => ['lock', 'border-slate-400 bg-slate-100 text-slate-500'],
        };
    @endphp

    <x-input-error :messages="$errors->get('booking')" />

    {{-- Ringkasan --}}
    <div class="card flex flex-col justify-between gap-4 p-6 md:flex-row md:items-center">
        <div class="space-y-1.5">
            <h2 class="font-mono text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">Pemesanan {{ $booking->code }}</h2>
            <p class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <span class="material-symbols-outlined text-base text-slate-400" aria-hidden="true">schedule</span>
                Dibuat oleh {{ $booking->creator->name }} pada <strong class="font-medium text-slate-900">{{ $booking->created_at->format('d/m/Y H:i') }}</strong>
            </p>
        </div>
        <x-status-badge :status="$booking->status" size="lg" />
    </div>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
        {{-- Kiri --}}
        <div class="space-y-6 lg:col-span-8">
            <section class="card space-y-6 p-6">
                <div class="flex items-center gap-2.5 border-b border-slate-100 pb-4">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-brand-700"><span class="material-symbols-outlined" aria-hidden="true">assignment</span></span>
                    <div>
                        <h3 class="card-title">Detail Pemesanan</h3>
                        <p class="card-subtitle">Data pemohon dan jadwal perjalanan</p>
                    </div>
                </div>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                    <div class="space-y-1"><dt class="text-xs font-semibold text-slate-500">Pemohon</dt><dd class="font-semibold text-slate-900">{{ $booking->requester_name }}</dd></div>
                    <div class="space-y-1">
                        <dt class="text-xs font-semibold text-slate-500">Tujuan</dt>
                        <dd class="flex items-start gap-1.5 font-semibold text-slate-900"><span class="material-symbols-outlined mt-0.5 text-lg text-red-600" aria-hidden="true">location_on</span>{{ $booking->destination }}</dd>
                    </div>
                    <div class="space-y-1">
                        <dt class="text-xs font-semibold text-slate-500">Waktu mulai</dt>
                        <dd class="tabular flex items-center gap-1.5 font-semibold text-slate-900"><span class="material-symbols-outlined text-lg text-brand-700" aria-hidden="true">event</span>{{ $booking->start_at->format('d/m/Y, H:i') }}</dd>
                    </div>
                    <div class="space-y-1">
                        <dt class="text-xs font-semibold text-slate-500">Waktu selesai</dt>
                        <dd class="tabular flex flex-wrap items-center gap-2 font-semibold text-slate-900">
                            <span class="material-symbols-outlined text-lg text-brand-700" aria-hidden="true">event_available</span>{{ $booking->end_at->format('d/m/Y, H:i') }}
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Durasi: {{ rtrim(rtrim(number_format($hours, 1, ',', ''), '0'), ',') }} jam</span>
                        </dd>
                    </div>
                </dl>
                <div class="space-y-2 border-t border-slate-100 pt-4">
                    <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-500"><span class="material-symbols-outlined text-base" aria-hidden="true">notes</span>Keperluan</span>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 leading-relaxed text-slate-800">{{ $booking->purpose }}</div>
                </div>
            </section>

            <section class="card space-y-6 p-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-brand-700"><span class="material-symbols-outlined" aria-hidden="true">directions_car</span></span>
                        <div>
                            <h3 class="card-title">Kendaraan &amp; Driver</h3>
                            <p class="card-subtitle">Unit kendaraan dan driver yang ditugaskan</p>
                        </div>
                    </div>
                </div>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                    <div class="space-y-1">
                        <dt class="text-xs font-semibold text-slate-500">Kendaraan</dt>
                        <dd class="flex flex-wrap items-center gap-2 font-semibold text-slate-900">
                            <span class="rounded bg-sidebar px-2 py-0.5 font-mono text-xs font-bold text-white">{{ $booking->vehicle->plate_number }}</span>{{ $booking->vehicle->brand_model }}
                        </dd>
                    </div>
                    <div class="space-y-1">
                        <dt class="text-xs font-semibold text-slate-500">Jenis angkutan</dt>
                        <dd class="font-medium text-slate-900">{{ $booking->vehicle->type->label() }}</dd>
                        @if ($booking->vehicle->capacity)<dd class="text-[11px] text-slate-500">Kapasitas: {{ $booking->vehicle->capacity }} {{ $booking->vehicle->type->value === 'cargo' ? 'ton' : 'penumpang' }}</dd>@endif
                    </div>
                    <div class="space-y-1">
                        <dt class="text-xs font-semibold text-slate-500">Kepemilikan</dt>
                        <dd class="font-medium text-slate-900">{{ $booking->vehicle->ownership->label() }}@if ($booking->vehicle->rentalCompany) &mdash; {{ $booking->vehicle->rentalCompany->name }}@endif</dd>
                    </div>
                    <div class="space-y-1">
                        <dt class="text-xs font-semibold text-slate-500">Lokasi pool</dt>
                        <dd class="flex items-center gap-1.5 font-medium text-slate-900"><span class="material-symbols-outlined text-lg text-brand-700" aria-hidden="true">warehouse</span>{{ $booking->vehicle->location->name }}</dd>
                    </div>
                    <div class="space-y-1"><dt class="text-xs font-semibold text-slate-500">Driver</dt><dd class="font-semibold text-slate-900">{{ $booking->driver->name }}</dd></div>
                    <div class="space-y-1">
                        <dt class="text-xs font-semibold text-slate-500">Telepon driver</dt>
                        <dd class="tabular flex items-center gap-2 font-mono font-medium text-slate-900"><span class="material-symbols-outlined text-lg text-green-700" aria-hidden="true">call</span>{{ $booking->driver->phone ?? '-' }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        {{-- Kanan: persetujuan --}}
        <div class="space-y-6 lg:col-span-4">
            <section class="card space-y-6 p-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[22px] text-brand-700" aria-hidden="true">verified</span>
                        <h3 class="card-title">Alur Persetujuan</h3>
                    </div>
                    <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">{{ $booking->approvals->count() }} Level</span>
                </div>

                <ol class="relative space-y-6 pl-6 before:absolute before:bottom-2 before:left-[11px] before:top-2 before:w-0.5 before:bg-slate-300">
                    @foreach ($booking->approvals as $approval)
                        @php [$icon, $tone] = $marker($approval->status); @endphp
                        <li class="relative space-y-2">
                            <span class="absolute -left-[30px] top-0 flex h-6 w-6 items-center justify-center rounded-full border-2 {{ $tone }}">
                                <span class="material-symbols-outlined text-sm font-bold" aria-hidden="true">{{ $icon }}</span>
                            </span>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="block text-xs font-semibold text-slate-900">Level {{ $approval->level }} &mdash; {{ $approval->approver->name }}</span>
                                    @if ($approval->approver->position)<span class="block text-xs text-slate-500">{{ $approval->approver->position }}</span>@endif
                                </div>
                                <x-status-badge :status="$approval->status" class="shrink-0 text-[11px]" />
                            </div>
                            @if ($approval->acted_at)
                                <div class="tabular flex items-center gap-1 font-mono text-[11px] text-slate-500"><span class="material-symbols-outlined text-[13px]" aria-hidden="true">done_all</span>{{ $approval->acted_at->format('d/m/Y H:i') }}</div>
                            @endif
                            @if ($approval->note)
                                <blockquote class="rounded-r border-l-2 border-brand-700 bg-slate-50 p-2.5 text-xs italic text-slate-800">&ldquo;{{ $approval->note }}&rdquo;</blockquote>
                            @endif
                            @if ($approval->status === \App\Enums\ApprovalStatus::Pending && ! ($myTurn && $myTurn->is($approval)))
                                <p class="text-[12px] text-slate-500">Sedang menunggu keputusan approver level {{ $approval->level }}.</p>
                            @elseif ($approval->status === \App\Enums\ApprovalStatus::Waiting)
                                <p class="text-[12px] text-slate-500">Dapat memproses setelah level {{ $approval->level - 1 }} menyetujui.</p>
                            @endif
                        </li>
                    @endforeach
                </ol>

                @if ($myTurn && auth()->user()->can('act', $myTurn))
                    <div class="space-y-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <div class="flex items-start gap-2.5">
                            <span class="material-symbols-outlined mt-0.5 shrink-0 text-amber-600" aria-hidden="true">notification_important</span>
                            <div>
                                <h4 class="font-semibold text-amber-950">Pemesanan ini menunggu persetujuan Anda (level {{ $myTurn->level }}).</h4>
                                <p class="mt-0.5 text-xs leading-snug text-amber-800">Pastikan jadwal, kendaraan, dan driver sudah sesuai sebelum memutuskan.</p>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('approval')" />
                        @include('approvals._actions', ['approval' => $myTurn->setRelation('booking', $booking), 'stretch' => true])
                    </div>
                @endif

                @can('cancel', $booking)
                    <div class="space-y-2 border-t border-slate-100 pt-4">
                        <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Tindakan Admin</span>
                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm('Batalkan pemesanan {{ $booking->code }}?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-danger-outline w-full py-2.5">
                                <span class="material-symbols-outlined text-lg" aria-hidden="true">cancel</span>Batalkan Pemesanan
                            </button>
                        </form>
                    </div>
                @endcan
            </section>
        </div>
    </div>
</x-app-layout>
