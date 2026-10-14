<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Log Aktivitas</h2>
        <p class="text-sm text-gray-500">Riwayat seluruh proses: login, pemesanan, persetujuan, laporan, dan perubahan data armada.</p>
    </x-slot>

    @php
        $selectClass = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
        $badge = [
            'auth' => 'bg-gray-100 text-gray-700',
            'booking' => 'bg-blue-100 text-blue-800',
            'approval' => 'bg-green-100 text-green-800',
            'report' => 'bg-purple-100 text-purple-800',
            'fleet' => 'bg-orange-100 text-orange-800',
        ];
        $fmt = fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (is_bool($v) ? ($v ? 'ya' : 'tidak') : ($v ?? '-'));
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-4 px-4 sm:px-6 lg:px-8">
            <form method="GET" class="grid grid-cols-1 gap-3 rounded-lg bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
                <div class="lg:col-span-2">
                    <x-input-label for="q" value="Cari deskripsi" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$filters['q'] ?? ''" placeholder="mis. BK-202610" />
                </div>
                <div>
                    <x-input-label for="log" value="Kategori" />
                    <select id="log" name="log" class="{{ $selectClass }}">
                        <option value="">Semua</option>
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['log'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="user" value="User" />
                    <select id="user" name="user" class="{{ $selectClass }}">
                        <option value="">Semua</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) ($filters['user'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="from" value="Dari" />
                    <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="$filters['from'] ?? ''" />
                </div>
                <div>
                    <x-input-label for="to" value="Sampai" />
                    <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="$filters['to'] ?? ''" />
                </div>
                <div class="flex gap-2 sm:col-span-2 lg:col-span-6">
                    <x-primary-button>Filter</x-primary-button>
                    <a href="{{ route('activity-logs.index') }}" class="px-2 py-2 text-xs font-semibold uppercase tracking-widest text-gray-600 hover:text-gray-900">Reset</a>
                </div>
                <x-input-error :messages="$errors->all()" class="sm:col-span-2 lg:col-span-6" />
            </form>

            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Aktivitas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($activities as $activity)
                            @php
                                $props = $activity->properties?->toArray() ?? [];
                                $changes = $props['attributes'] ?? null;
                                $old = $props['old'] ?? [];
                                $extra = collect($props)->except(['attributes', 'old'])->all();
                            @endphp
                            <tr class="align-top hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-600">{{ $activity->created_at->format('d/m/Y H:i:s') }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $activity->causer?->name ?? 'Sistem / tamu' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge[$activity->log_name] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $categories[$activity->log_name] ?? $activity->log_name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-gray-900">
                                        {{ $activity->description }}
                                        @if ($activity->subject instanceof \App\Models\Booking)
                                            <a href="{{ route('bookings.show', $activity->subject) }}" class="ms-1 text-xs text-indigo-600 hover:text-indigo-900">lihat</a>
                                        @endif
                                    </div>

                                    @if ($changes || $extra)
                                        <details class="mt-1">
                                            <summary class="cursor-pointer text-xs text-gray-500 hover:text-gray-700">Detail</summary>
                                            <dl class="mt-2 grid grid-cols-1 gap-x-4 gap-y-1 rounded bg-gray-50 p-3 text-xs sm:grid-cols-2">
                                                @foreach ($changes ?? [] as $field => $value)
                                                    <div>
                                                        <dt class="inline font-medium text-gray-600">{{ $field }}:</dt>
                                                        <dd class="inline text-gray-800">
                                                            @if (array_key_exists($field, $old))
                                                                <span class="text-red-600 line-through">{{ $fmt($old[$field]) }}</span> &rarr;
                                                            @endif
                                                            {{ $fmt($value) }}
                                                        </dd>
                                                    </div>
                                                @endforeach
                                                @foreach ($extra as $key => $value)
                                                    <div>
                                                        <dt class="inline font-medium text-gray-600">{{ $key }}:</dt>
                                                        <dd class="inline break-all text-gray-800">{{ $fmt($value) }}</dd>
                                                    </div>
                                                @endforeach
                                            </dl>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Belum ada aktivitas yang cocok dengan filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $activities->links() }}</div>
        </div>
    </div>
</x-app-layout>
