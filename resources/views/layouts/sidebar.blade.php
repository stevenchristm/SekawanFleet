@php
    $user = Auth::user();
    $menu = array_filter([
        ['route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard', 'show' => true],
        ['route' => 'bookings.index', 'match' => 'bookings.*', 'icon' => 'calendar_today', 'label' => 'Pemesanan', 'show' => $user->isAdmin()],
        ['route' => 'approvals.index', 'match' => 'approvals.*', 'icon' => 'fact_check', 'label' => 'Persetujuan Saya', 'show' => $user->isApprover(), 'badge' => $pendingApprovals],
        ['route' => 'reports.index', 'match' => 'reports.*', 'icon' => 'analytics', 'label' => 'Laporan', 'show' => $user->isAdmin()],
        ['route' => 'activity-logs.index', 'match' => 'activity-logs.*', 'icon' => 'history', 'label' => 'Log Aktivitas', 'show' => $user->isAdmin()],
    ], fn ($item) => $item['show']);
@endphp

<aside class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col justify-between bg-sidebar transition-transform duration-200 ease-out lg:translate-x-0"
       :class="sidebarOpen && 'translate-x-0'" aria-label="Navigasi utama">
    <div class="flex min-h-0 flex-col gap-6 overflow-y-auto p-4">
        {{-- Brand --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-1">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-700 text-white shadow-sm">
                    <span class="material-symbols-outlined text-2xl" aria-hidden="true">local_shipping</span>
                </span>
                <span>
                    <span class="block text-lg font-bold leading-tight tracking-tight text-white">SekawanFleet</span>
                    <span class="block text-[11px] font-semibold text-sidebar-muted">Operasi Tambang Nikel</span>
                </span>
            </a>
            <button type="button" class="rounded-lg p-1.5 text-sidebar-muted hover:bg-sidebar-hover hover:text-white lg:hidden"
                    @click="sidebarOpen = false" aria-label="Tutup menu">
                <span class="material-symbols-outlined" aria-hidden="true">close</span>
            </button>
        </div>

        {{-- Cakupan operasional --}}
        <div class="rounded-lg border border-white/5 bg-sidebar-hover/60 p-2.5">
            <div class="text-[10px] font-bold uppercase tracking-wider text-sidebar-muted">Cakupan Operasional</div>
            <div class="mt-0.5 text-xs font-medium text-white">{{ $locationSummary['total'] }} Lokasi</div>
            <div class="text-[11px] text-sidebar-muted">{{ $locationSummary['label'] }}</div>
        </div>

        {{-- Menu --}}
        <nav class="space-y-1">
            @foreach ($menu as $item)
                @php $active = request()->routeIs($item['match']); @endphp
                <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                   @class([
                       'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                       'bg-brand-700 text-white shadow-sm' => $active,
                       'text-slate-300 hover:bg-sidebar-hover hover:text-white' => ! $active,
                   ])>
                    <span @class(['material-symbols-outlined text-[20px]', 'icon-fill' => $active]) aria-hidden="true">{{ $item['icon'] }}</span>
                    <span class="flex-1">{{ $item['label'] }}</span>
                    @if (! empty($item['badge']))
                        <span class="rounded-full bg-red-500 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white" aria-label="{{ $item['badge'] }} menunggu">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>

    {{-- User card --}}
    <div class="space-y-2 border-t border-white/10 p-4">
        <div class="flex items-center justify-between rounded-lg bg-sidebar-hover/60 p-2">
            <div class="flex min-w-0 items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-brand-700 bg-brand-700/30 text-xs font-semibold text-brand-100">{{ $user->initials() }}</span>
                <div class="min-w-0">
                    <div class="truncate text-xs font-semibold text-white">{{ $user->name }}</div>
                    <span @class([
                        'inline-block rounded border px-1.5 text-[10px] font-medium',
                        'border-emerald-800/60 bg-emerald-950 text-emerald-300' => $user->isAdmin(),
                        'border-amber-500/40 bg-amber-500/20 text-amber-300' => ! $user->isAdmin(),
                    ])>{{ $user->role->label() }}</span>
                </div>
            </div>
            <a href="{{ route('profile.edit') }}" class="rounded p-1 text-sidebar-muted hover:text-white" title="Profil">
                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">settings</span>
                <span class="sr-only">Profil</span>
            </a>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-sidebar-muted transition-colors hover:bg-sidebar-hover hover:text-red-400">
                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">logout</span>
                Keluar
            </button>
        </form>
    </div>
</aside>
