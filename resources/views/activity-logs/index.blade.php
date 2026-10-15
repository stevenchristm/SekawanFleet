<x-app-layout>
    <x-slot name="title">Log Aktivitas</x-slot>
    <x-slot name="subtitle">Riwayat seluruh proses: login, pemesanan, persetujuan, laporan, dan perubahan data armada.</x-slot>

    @php
        $badge = [
            'auth' => ['bg-slate-100 text-slate-800', 'bg-slate-600'],
            'booking' => ['bg-blue-100 text-blue-800', 'bg-blue-600'],
            'approval' => ['bg-emerald-100 text-emerald-800', 'bg-emerald-600'],
            'report' => ['bg-purple-100 text-purple-800', 'bg-purple-600'],
            'fleet' => ['bg-amber-100 text-amber-800', 'bg-amber-600'],
            'service' => ['bg-cyan-100 text-cyan-800', 'bg-cyan-600'],
        ];
        $fmt = fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (is_bool($v) ? ($v ? 'ya' : 'tidak') : ($v ?? '-'));
    @endphp

    {{-- Filter --}}
    <section class="card p-5">
        <form method="GET" class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label for="q" class="form-label">Cari deskripsi</label>
                <div class="relative">
                    <span class="material-symbols-outlined pointer-events-none absolute inset-y-0 left-3 my-auto text-base text-slate-400" aria-hidden="true">search</span>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="mis. BK-202610" class="form-input pl-9">
                </div>
            </div>
            <div>
                <label for="log" class="form-label">Kategori</label>
                <select id="log" name="log" class="form-input">
                    <option value="">Semua</option>
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['log'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="user" class="form-label">User</label>
                <select id="user" name="user" class="form-input">
                    <option value="">Semua user</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) ($filters['user'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="form-label">Dari</label>
                <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="form-input">
            </div>
            <div>
                <label for="to" class="form-label">Sampai</label>
                <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="form-input">
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-3 sm:col-span-2 lg:col-span-6">
                <a href="{{ route('activity-logs.index') }}" class="px-3 py-1.5 text-sm text-slate-500 hover:text-slate-900">Reset</a>
                <button type="submit" class="btn-primary h-[38px]"><span class="material-symbols-outlined text-base" aria-hidden="true">filter_alt</span>Filter</button>
            </div>
        </form>
        <x-input-error :messages="$errors->all()" class="mt-3" />
    </section>

    {{-- Tabel --}}
    <section class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Waktu</th><th>User</th><th>Kategori</th><th>Aktivitas</th></tr></thead>
                <tbody>
                    @forelse ($activities as $activity)
                        @php
                            $props = $activity->properties?->toArray() ?? [];
                            $changes = $props['attributes'] ?? null;
                            $old = $props['old'] ?? [];
                            $extra = collect($props)->except(['attributes', 'old'])->all();
                            [$pill, $dot] = $badge[$activity->log_name] ?? $badge['auth'];
                            $causer = $activity->causer;
                        @endphp
                        <tr>
                            <td class="tabular whitespace-nowrap font-mono text-xs text-slate-500">{{ $activity->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="whitespace-nowrap">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-200 text-[11px] font-bold text-slate-600">{{ $causer ? $causer->initials() : '?' }}</span>
                                    <span class="font-medium text-slate-900">{{ $causer?->name ?? 'Sistem / tamu' }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $pill }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>{{ $categories[$activity->log_name] ?? $activity->log_name }}
                                </span>
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-slate-900">{{ $activity->description }}</span>
                                    @if ($activity->subject instanceof \App\Models\Booking)
                                        <a href="{{ route('bookings.show', $activity->subject) }}" class="flex items-center gap-1 text-xs font-medium text-brand-700 hover:underline">lihat<span class="material-symbols-outlined text-sm" aria-hidden="true">visibility</span></a>
                                    @elseif ($activity->subject instanceof \App\Models\ServiceSchedule || $activity->subject instanceof \App\Models\Vehicle)
                                        <a href="{{ route('vehicles.show', $activity->subject instanceof \App\Models\Vehicle ? $activity->subject : $activity->subject->vehicle_id) }}" class="flex items-center gap-1 text-xs font-medium text-brand-700 hover:underline">lihat<span class="material-symbols-outlined text-sm" aria-hidden="true">visibility</span></a>
                                    @endif
                                </div>

                                @if ($changes || $extra)
                                    <details class="group mt-1">
                                        <summary class="flex cursor-pointer list-none items-center gap-1 text-xs text-slate-500 hover:text-slate-700">
                                            <span class="material-symbols-outlined text-base transition-transform group-open:rotate-180" aria-hidden="true">expand_more</span>Detail
                                        </summary>
                                        <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50 p-4 font-mono text-xs">
                                            <div class="mb-2 flex items-center gap-1.5 font-sans text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                                <span class="material-symbols-outlined text-sm" aria-hidden="true">code</span>Detail perubahan data
                                            </div>
                                            <dl class="grid grid-cols-1 gap-x-6 gap-y-1.5 md:grid-cols-2">
                                                @foreach ($changes ?? [] as $field => $value)
                                                    <div class="flex items-center justify-between gap-3 border-b border-slate-200/70 py-1">
                                                        <dt class="text-slate-500">{{ $field }}:</dt>
                                                        <dd class="break-all text-right font-semibold text-slate-900">
                                                            @if (array_key_exists($field, $old))<span class="font-normal text-red-600 line-through">{{ $fmt($old[$field]) }}</span> &rarr; @endif{{ $fmt($value) }}
                                                        </dd>
                                                    </div>
                                                @endforeach
                                                @foreach ($extra as $key => $value)
                                                    <div class="flex items-center justify-between gap-3 border-b border-slate-200/70 py-1">
                                                        <dt class="text-slate-500">{{ $key }}:</dt>
                                                        <dd class="break-all text-right font-semibold text-slate-900">{{ $fmt($value) }}</dd>
                                                    </div>
                                                @endforeach
                                            </dl>
                                        </div>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-12 text-center text-slate-500">Belum ada aktivitas yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $activities->links() }}
    </section>
</x-app-layout>
