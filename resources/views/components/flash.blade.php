@foreach (['success' => ['bg-green-50 border-green-300 text-green-800', 'check_circle', 'status'], 'error' => ['bg-red-50 border-red-300 text-red-800', 'error', 'alert']] as $key => [$tone, $icon, $role])
    @if (session($key))
        <div x-data="{ open: true }" x-show="open" role="{{ $role }}"
             class="flex items-start justify-between gap-3 rounded-xl border p-4 shadow-sm {{ $tone }}">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined icon-fill" aria-hidden="true">{{ $icon }}</span>
                <p class="font-medium">{{ session($key) }}</p>
            </div>
            <button type="button" @click="open = false" class="rounded p-0.5 opacity-70 hover:opacity-100" aria-label="Tutup notifikasi">
                <span class="material-symbols-outlined text-lg" aria-hidden="true">close</span>
            </button>
        </div>
    @endif
@endforeach
